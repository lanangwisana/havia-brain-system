<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

// Kita extends ResourceController untuk fitur API yang lengkap
class EventsApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
    protected $settings_model;
    protected $events_model;
    protected $users_model;
    protected $api_settings_model;

    public function __construct() {
        // 1. LOAD HELPERS PENTING (Ini solusi untuk Error 500)
        helper(['date_time', 'general', 'app_files', 'url']);
        
        // Load JWT helper dari plugin RestApi secara manual agar pasti terdeteksi
        if (file_exists(PLUGINPATH . "RestApi/Helpers/jwt_helper.php")) {
            require_once PLUGINPATH . "RestApi/Helpers/jwt_helper.php";
        }
        
        // 2. Load Models
        $this->settings_model = model('App\Models\Settings_model');
        $this->events_model = model('App\Models\Events_model');
        $this->users_model = model('App\Models\Users_model');
        $this->api_settings_model = model('RestApi\Models\Api_settings_model');

        // 3. Load App Settings (Penting untuk timezone dll)
        $this->_load_settings();
    }

    /**
     * Memuat setting dari database ke config agar get_setting() berfungsi
     */
    private function _load_settings($user_id = 0) {
        $settings = $this->settings_model->get_all_required_settings($user_id)->getResult();
        foreach ($settings as $setting) {
            config('Rise')->app_settings_array[$setting->setting_name] = $setting->setting_value;
        }
    }

    /**
     * Endpoint: GET /api/haviacms/events
     * Melayani data event dengan filter yang sudah diperbaiki
     */
    public function index() {
        try {
            // 1. Validasi Token
            $is_valid_token = validateToken();
            $token = get_token();
            $check_token = $this->api_settings_model->check_token($token);

            if ($is_valid_token['status'] == false || $check_token === false) {
                return $this->failUnauthorized($is_valid_token['message'] ?? "Token tidak valid atau sudah kadaluarsa.");
            }

            // 2. Ambil User ID dari Token
            $token_data = $is_valid_token['data'];
            
            $user_id = null;
            $user_row = null;
            
            if (isset($token_data->id)) {
                $user_id = $token_data->id;
            } else if (isset($token_data->crm_user_id)) {
                $user_id = $token_data->crm_user_id;
            } else if (isset($token_data->user)) {
                // FALLBACK: Jika ID tidak ada, cari berdasarkan email (field 'user' di token)
                $user_row = $this->users_model->get_one_where(['email' => $token_data->user, 'deleted' => 0]);
                if (isset($user_row->id)) {
                    $user_id = $user_row->id;
                }
            }

            if (!$user_id) {
                return $this->fail('Gagal mengidentifikasi User dari token. Pastikan email terdaftar.', 400);
            }
            
            // Reload settings untuk user spesifik (timezone dsb bisa berbeda per user)
            $this->_load_settings($user_id);
            
            // Ambil data user lengkap jika belum ada (untuk ambil team_ids dll)
            if (!$user_row) {
                $user_row = $this->users_model->get_one($user_id);
            }

            // 3. Ambil Detail Event
            $options = [
                'login_user_id' => $user_id,
                'user_id' => $user_id,
                'team_ids' => isset($user_row->team_ids) ? $user_row->team_ids : ""
            ];

            $list_data = $this->events_model->get_details($options)->getResult();

            return $this->respond($list_data, 200);
            
        } catch (\Throwable $e) {
            // Tangkap Error dan kembalikan pesan detail untuk debug
            return $this->respond([
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
