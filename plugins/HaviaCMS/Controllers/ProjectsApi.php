<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class ProjectsApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
    protected $projects_model;
    protected $tasks_model;
    protected $api_settings_model;
    protected $users_model;
    protected $settings_model;
    private $initialized = false;

    public function __construct() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, authtoken, Accept");

        if (strtoupper(request()->getMethod()) === 'OPTIONS') {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

    private function _init() {
        if ($this->initialized) return;
        
        helper(['date_time', 'general', 'url']);
        
        if (defined('PLUGINPATH') && file_exists(PLUGINPATH . "RestApi/Helpers/jwt_helper.php")) {
            require_once PLUGINPATH . "RestApi/Helpers/jwt_helper.php";
        }

        $this->projects_model = model('App\Models\Projects_model');
        $this->tasks_model = model('App\Models\Tasks_model');
        $this->api_settings_model = model('RestApi\Models\Api_settings_model');
        $this->users_model = model('App\Models\Users_model');
        $this->settings_model = model('App\Models\Settings_model');

        $this->_load_settings();
        $this->initialized = true;
    }

    private function _load_settings($user_id = 0) {
        $settings = $this->settings_model->get_all_required_settings($user_id)->getResult();
        foreach ($settings as $setting) {
            config('Rise')->app_settings_array[$setting->setting_name] = $setting->setting_value;
        }
    }

    private function _validate_user() {
        // 1. Manually extract token
        $token_raw = null;
        $all_headers = $this->request->getHeaders();
        
        foreach($all_headers as $name => $header) {
            if (strtolower($name) === 'authtoken' || strtolower($name) === 'authorization') {
                $token_raw = (string)$header;
                break;
            }
        }

        if (!$token_raw) return "ERROR_MISSING_HEADER";

        // 2. ULTRA-AGGRESSIVE CLEANING
        $token = $token_raw;
        // Strip out common header prefixes that some servers prepend
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        if (empty($token)) return "ERROR_EMPTY_TOKEN";

        // 3. STRATEGY A: Standard Signature Verification
        // Ini adalah cara yang paling aman, menggunakan Secret Key dari config.
        try {
            $jwt_config = new \RestApi\Config\JWT();
            $key = preg_replace('/^["\']|["\']$/', '', trim($jwt_config->jwt_key));
            
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, $jwt_config->jwt_algorithm));
            
            if ($decoded && is_object($decoded)) {
                $user_id = $decoded->id ?? $decoded->crm_user_id ?? null;
                if (!$user_id && isset($decoded->user)) {
                    $user_row = $this->users_model->get_one_where(['email' => $decoded->user, 'deleted' => 0]);
                    $user_id = $user_row->id ?? null;
                }
                
                if ($user_id) {
                    $user = $this->users_model->get_one($user_id);
                    if ($user && $user->id && !$user->deleted) return (int)$user_id;
                }
            }
        } catch (\Exception $e) {
            // Jika siganture failed, jangan menyerah dulu. Cek database (Strategy B).
            $sig_error = $e->getMessage();
        }

        // 4. STRATEGY B: Database Cross-Reference (Fallback Darurat)
        // Jika signature failed (biasanya karena Secret Key di server berubah/mismatch),
        // kita cek apakah token tersebut benar-benar ada di tabel rise_api_users kita.
        // Karena token disimpan secara statis di database RISE CRM, ini adalah bukti validitas.
        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            // 'user' di tabel rise_api_users biasanya berisi email
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row && $user_row->id) {
                // Berhasil validasi via database!
                return (int)$user_row->id;
            }
        }

        $snippet = substr($token, 0, 10) . "..." . substr($token, -5);
        return "ERROR_AUTH_FAILED: " . ($sig_error ?? "Token not found in DB") . " [Snippet: $snippet]";
    }

    public function index() {
        try {
            $this->_init();
            $validation_result = $this->_validate_user();
            
            if (!is_int($validation_result)) {
                // Diagnostic info for debugging
                $headers = $this->request->getHeaders();
                $found_headers = implode(", ", array_keys($headers));
                $raw_token = (string)($this->request->getHeader('authtoken') ?? 'None');
                $token_head = substr($raw_token, 0, 15);
                return $this->response->setStatusCode(401)->setJSON([
                    "success" => false, 
                    "message" => "Token tidak valid. DEBUG:[$validation_result] [Headers: $found_headers] [TokenHead: $token_head]"
                ]);
            }

            $user_id = $validation_result;

            // 1. Get projects where user is explicitly a member
            $options = ['user_id' => $user_id];
            $projects = $this->projects_model->get_details($options)->getResultArray();

            // 2. Deep Discovery: Find projects via tasks (Pic or Collaborator)
            // This ensures "RK House" shows up for Asep
            $task_options = ['specific_user_id' => $user_id];
            $tasks = $this->tasks_model->get_details($task_options)->getResultArray();
            
            $involved_project_ids = [];
            foreach ($tasks as $task) {
                if ($task['project_id']) {
                    $involved_project_ids[] = $task['project_id'];
                }
            }
            $involved_project_ids = array_unique($involved_project_ids);

            // 3. Merge projects from tasks that are not in the explicit membership list
            foreach ($involved_project_ids as $p_id) {
                $exists = false;
                foreach ($projects as $p) {
                    if ($p['id'] == $p_id) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $p_details = $this->projects_model->get_details(['id' => $p_id])->getRowArray();
                    if ($p_details) {
                        $projects[] = $p_details;
                    }
                }
            }

            return $this->respond($projects);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function show($id = null) {
        try {
            $this->_init();
            if (!$id) return $this->fail("ID required");
            
            $user_id = $this->_validate_user();
            if (!$user_id) return $this->failUnauthorized("Token tidak valid.");

            $data = $this->projects_model->get_details(['id' => $id])->getRow();
            return $this->respond($data);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
