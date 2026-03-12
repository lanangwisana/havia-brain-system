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
        $is_valid_token = validateToken();
        $token = get_token();
        $check_token = $this->api_settings_model->check_token($token);

        if ($is_valid_token['status'] == false || $check_token === false) {
            return false;
        }

        $token_data = $is_valid_token['data'];
        $user_id = null;

        if (isset($token_data->id)) {
            $user_id = $token_data->id;
        } else if (isset($token_data->crm_user_id)) {
            $user_id = $token_data->crm_user_id;
        } else if (isset($token_data->user)) {
            $user_row = $this->users_model->get_one_where(['email' => $token_data->user, 'deleted' => 0]);
            if (isset($user_row->id)) {
                $user_id = $user_row->id;
            }
        }

        return $user_id;
    }

    public function index() {
        try {
            $this->_init();
            $user_id = $this->_validate_user();
            if (!$user_id) return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Token tidak valid."]);

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
            $user_id = $this->_validate_user();
            if (!$user_id) {
                return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Token tidak valid (Unauthorized)"]);
            }

            $in_time = isset($this->request_data['in_time']) ? $this->request_data['in_time'] : get_current_utc_time();
            $note = isset($this->request_data['note']) ? $this->request_data['note'] : "Clock in via Mobile (HaviaCMS API)";

            // Cek jika sudah ada yang aktif
            $active = $this->attendance_model->current_clock_in_record($user_id);
            if ($active) {
                return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "Anda masih memiliki sesi aktif. Silahkan Clock Out terlebih dahulu."]);
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
            $user_id = $this->_validate_user();
            if (!$user_id) {
                return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Token tidak valid (Unauthorized)"]);
            }
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
            $user_id = $this->_validate_user();
            if (!$user_id) {
                return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Token tidak valid (Unauthorized)"]);
            }
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
