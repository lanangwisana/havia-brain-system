<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class LeavesApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
    protected $leave_applications_model;
    protected $leave_types_model;
    protected $api_settings_model;
    protected $users_model;
    protected $settings_model;
    protected $request_data = [];
    private $initialized = false;

    public function __construct() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, authtoken, Accept, Origin, X-Requested-With");
        
        if (strtoupper(request()->getMethod()) === 'OPTIONS') {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

    private function _init() {
        if ($this->initialized) return;
        
        $this->request_data = $this->request->getPost();
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            try {
                $json = $this->request->getJSON(true);
                if ($json && is_array($json)) {
                    $this->request_data = array_merge($this->request_data, $json);
                }
            } catch (\Exception $e) {}
        }

        helper(['date_time', 'general', 'url']);
        
        if (file_exists(ROOTPATH . "plugins/RestApi/Helpers/jwt_helper.php")) {
            require_once ROOTPATH . "plugins/RestApi/Helpers/jwt_helper.php";
        }

        $this->leave_applications_model = model('App\Models\Leave_applications_model');
        $this->leave_types_model = model('App\Models\Leave_types_model');
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
        $token_raw = $this->request->getHeaderLine('authtoken') ?: $this->request->getHeaderLine('Authorization');
        if (!$token_raw) return "ERROR_MISSING_HEADER";

        $token = trim(preg_replace('/^(bearer):?\s+/i', '', $token_raw));
        if (empty($token)) return "ERROR_EMPTY_TOKEN";

        try {
            $jwt_config = new \RestApi\Config\JWT();
            $key = preg_replace('/^["\']|["\']$/', '', trim($jwt_config->jwt_key));
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, $jwt_config->jwt_algorithm));
            
            if ($decoded && is_object($decoded)) {
                $user_id = $decoded->id ?? $decoded->crm_user_id ?? null;
                if ($user_id) {
                    $user = $this->users_model->get_one($user_id);
                    if ($user && $user->id && !$user->deleted) return (int)$user_id;
                }
            }
        } catch (\Exception $e) {}

        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row && $user_row->id) return (int)$user_row->id;
        }

        return "ERROR_AUTH_FAILED";
    }

    public function index() {
        try {
            $this->_init();
            $validation = $this->_validate_user();
            if (!is_int($validation)) return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized"]);
            
            $user_id = $validation;
            
            // Menggunakan query manual agar bisa mengambil kolom 'reason' tanpa merubah model core
            $db = \Config\Database::connect();
            $builder = $db->table('leave_applications');
            $builder->select('leave_applications.*, leave_types.title as leave_type_title, leave_types.color as leave_type_color');
            $builder->join('leave_types', 'leave_types.id = leave_applications.leave_type_id', 'left');
            $builder->where('leave_applications.applicant_id', $user_id);
            $builder->where('leave_applications.deleted', 0);
            $builder->orderBy('leave_applications.start_date', 'DESC');
            
            $data = $builder->get()->getResult();
            return $this->respond(["success" => true, "data" => $data]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function leave_types() {
        try {
            $this->_init();
            $validation = $this->_validate_user();
            if (!is_int($validation)) return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized"]);
            
            // Menggunakan get_all_where dari Crud_model karena model aslinya mungkin membatasi filter
            $data = $this->leave_types_model->get_all_where(['status' => 'active', 'deleted' => 0])->getResult();
            return $this->respond(["success" => true, "data" => $data]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function create() {
        try {
            $this->_init();
            $validation = $this->_validate_user();
            if (!is_int($validation)) return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized"]);
            
            $user_id = $validation;
            
            $leave_type_id = $this->request_data['leave_type_id'] ?? null;
            $start_date = $this->request_data['start_date'] ?? null;
            $end_date = $this->request_data['end_date'] ?? null;
            $reason = $this->request_data['reason'] ?? "";
            
            if (!$leave_type_id || !$start_date || !$end_date) {
                return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "Data tidak lengkap."]);
            }

            // Calculate total days
            $d_start = new \DateTime($start_date);
            $d_end = new \DateTime($end_date);
            $d_diff = $d_start->diff($d_end);
            $days = $d_diff->days + 1;
            $hours = $days * 8; // Default 8 hours per day

            $data = [
                "leave_type_id" => $leave_type_id,
                "start_date" => $start_date,
                "end_date" => $end_date,
                "applicant_id" => $user_id,
                "reason" => $reason,
                "created_by" => $user_id,
                "created_at" => get_current_utc_time(),
                "total_hours" => $hours,
                "total_days" => $days,
                "status" => "pending"
            ];

            if ($this->leave_applications_model->ci_save($data)) {
                return $this->response->setJSON(["success" => true, "message" => "Pengajuan cuti/izin berhasil dikirim."]);
            }
            
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "Gagal menyimpan pengajuan."]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => $e->getMessage()]);
        }
    }
}