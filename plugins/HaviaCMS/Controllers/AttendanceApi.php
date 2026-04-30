<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class AttendanceApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
    protected $attendance_model;
    protected $api_settings_model;
    protected $users_model;
    protected $settings_model;
    protected $request_data = [];
    private $initialized = false;

    public function __construct() {
        // Global CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, authtoken, Accept, Origin, X-Requested-With");
        header("Access-Control-Max-Age: 86400"); // Cache for 24 hours

        if (strtoupper(request()->getMethod()) === 'OPTIONS') {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

    private function _init() {
        if ($this->initialized) return;
        
        // Handle JSON Request Body
        $this->request_data = $this->request->getPost();
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            try {
                $json = $this->request->getJSON(true);
                if ($json && is_array($json)) {
                    $this->request_data = array_merge($this->request_data, $json);
                }
            } catch (\Exception $e) {
                // Ignore malformed JSON
            }
        }

        helper(['date_time', 'general', 'url']);
        
        // Load JWT helper dari plugin RestApi (Sangat Penting)
        if (defined('PLUGINPATH') && file_exists(PLUGINPATH . "RestApi/Helpers/jwt_helper.php")) {
            require_once PLUGINPATH . "RestApi/Helpers/jwt_helper.php";
        } else if (file_exists(ROOTPATH . "plugins/RestApi/Helpers/jwt_helper.php")) {
            require_once ROOTPATH . "plugins/RestApi/Helpers/jwt_helper.php";
        }

        $this->attendance_model = model('App\Models\Attendance_model');
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
                $raw_token = (string)($this->request->getHeaderLine('authtoken') ?: 'None');
                $token_head = substr($raw_token, 0, 15);
                return $this->response->setStatusCode(401)->setJSON([
                    "success" => false, 
                    "message" => "Token tidak valid. DEBUG:[$validation_result] [TokenHead: $token_head]"
                ]);
            }

            $user_id = $validation_result;

            $options = [
                'user_id' => $user_id,
                'login_user_id' => $user_id
            ];

            $data = $this->attendance_model->get_details($options)->getResult();
            return $this->respond($data);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "API Init Error (index): " . $e->getMessage()]);
        }
    }

    public function debug() {
        try {
            $this->_init();
            $db = \Config\Database::connect();
            $fields = $db->getFieldData($db->prefixTable('attendance'));
            return $this->response->setJSON($fields);
        } catch (\Throwable $e) {
             return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "API Debug Error: " . $e->getMessage()]);
        }
    }

    public function create() {
        try {
            $this->_init();
            $validation_result = $this->_validate_user();
            
            if (!is_int($validation_result)) {
                return $this->response->setStatusCode(401)->setJSON([
                    "success" => false, 
                    "message" => "Token tidak valid (Unauthorized). DEBUG:[$validation_result]"
                ]);
            }

            $user_id = $validation_result;

            $in_time = isset($this->request_data['in_time']) ? $this->request_data['in_time'] : get_current_utc_time();
            $note = isset($this->request_data['note']) ? $this->request_data['note'] : "Clock in via Mobile (HaviaCMS API)";

            // Cek jika sudah ada yang aktif
            $active = $this->attendance_model->current_clock_in_record($user_id);
            if ($active) {
                $active_date = date("d M Y H:i", strtotime($active->in_time));
                return $this->response->setStatusCode(400)->setJSON([
                    "success" => false, 
                    "message" => "Anda masih memiliki sesi aktif dari tanggal $active_date. Silahkan Clock Out terlebih dahulu agar data tetap akurat."
                ]);
            }

            $data = [
                "in_time" => $in_time,
                "status" => "incomplete",
                "user_id" => (int)$user_id,
                "note" => $note,
                "out_time" => null
            ];

            $db = \Config\Database::connect();
            $table_name = $db->prefixTable('attendance');
            $builder = $db->table($table_name);
            
            if ($builder->insert($data)) {
                $id = $db->insertID();
                return $this->response->setJSON(["success" => true, "id" => $id, "message" => "Clock In Berhasil."]);
            }
            
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "Gagal menyimpan ke database (Insert failed)."]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "API Error: " . $e->getMessage()]);
        }
    }

    public function update($id = null) {
        try {
            $this->_init();
            $validation_result = $this->_validate_user();
            
            if (!is_int($validation_result)) {
                return $this->response->setStatusCode(401)->setJSON([
                    "success" => false, 
                    "message" => "Token tidak valid (Unauthorized). DEBUG:[$validation_result]"
                ]);
            }

            $user_id = $validation_result;
            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "ID Absensi tidak ditemukan."]);
            }

            $out_time = isset($this->request_data['out_time']) ? $this->request_data['out_time'] : get_current_utc_time();
            $status = isset($this->request_data['status']) ? $this->request_data['status'] : "pending";
            $note = isset($this->request_data['note']) ? $this->request_data['note'] : null;

            $data = [
                "out_time" => $out_time,
                "status" => $status
            ];
            if ($note) $data["note"] = $note;

            if ($this->attendance_model->ci_save($data, $id)) {
                return $this->response->setJSON(["success" => true, "message" => "Clock Out Berhasil."]);
            }
            
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "Gagal update ke database."]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "API Error: " . $e->getMessage()]);
        }
    }

    public function delete($id = null) {
        try {
            $this->_init();
            $validation_result = $this->_validate_user();
            
            if (!is_int($validation_result)) {
                return $this->response->setStatusCode(401)->setJSON([
                    "success" => false, 
                    "message" => "Token tidak valid (Unauthorized). DEBUG:[$validation_result]"
                ]);
            }

            $user_id = $validation_result;
            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "ID tidak valid."]);
            }

            // Pastikan record ini milik user tersebut atau user adalah admin
            $record = $this->attendance_model->get_one($id);
            if (!$record || $record->deleted) {
                return $this->response->setStatusCode(404)->setJSON(["success" => false, "message" => "Data tidak ditemukan."]);
            }

            if ($record->user_id != $user_id) {
                $user_row = $this->users_model->get_one($user_id);
                if (!$user_row->is_admin) {
                    return $this->response->setStatusCode(403)->setJSON(["success" => false, "message" => "Anda tidak memiliki akses menghapus data ini."]);
                }
            }

            if ($this->attendance_model->delete($id)) {
                return $this->response->setJSON(["success" => true, "message" => "Data berhasil dihapus."]);
            }
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "Gagal menghapus data dari database."]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "API Error: " . $e->getMessage()]);
        }
    }
}