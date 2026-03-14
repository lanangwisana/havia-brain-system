<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class TasksApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
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
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        if (empty($token)) return "ERROR_EMPTY_TOKEN";

        // 3. STRATEGY A: Standard Signature Verification
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
            $sig_error = $e->getMessage();
        }

        // 4. STRATEGY B: Database Cross-Reference (Fallback)
        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row && $user_row->id) {
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
                // Diagnostic info
                $raw_token = (string)($this->request->getHeader('authtoken') ?? 'None');
                $token_head = substr($raw_token, 0, 15);
                return $this->response->setStatusCode(401)->setJSON([
                    "success" => false, 
                    "message" => "Token tidak valid (Tasks). DEBUG:[$validation_result] [TokenHead: $token_head]"
                ]);
            }

            $user_id = $validation_result;

            $project_id = $this->request->getGet('project_id');
            
            // Use specific_user_id to get tasks where user is PIC OR Collaborator
            $options = [
                'specific_user_id' => $user_id
            ];

            if ($project_id) {
                $options['project_id'] = $project_id;
            }

            $data = $this->tasks_model->get_details($options)->getResult();
            return $this->respond($data);
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

            $data = $this->tasks_model->get_details(['id' => $id])->getRow();
            return $this->respond($data);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
