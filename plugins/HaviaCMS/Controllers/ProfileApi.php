<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class ProfileApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
    protected $users_model;
    protected $api_settings_model;
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
        
        helper(['general', 'app_files', 'url']);
        
        $this->request_data = $this->request->getPost();
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            try {
                $json = $this->request->getJSON(true);
                if ($json && is_array($json)) {
                    $this->request_data = array_merge($this->request_data, $json);
                }
            } catch (\Exception $e) {}
        }

        $this->users_model = model('App\Models\Users_model');
        $this->api_settings_model = model('RestApi\Models\Api_settings_model');
        
        $this->initialized = true;
    }

    private function _validate_user() {
        $all_headers = $this->request->getHeaders();
        $token_raw = null;
        
        foreach($all_headers as $name => $header) {
            if (strtolower($name) === 'authtoken' || strtolower($name) === 'authorization') {
                $token_raw = (string)$header;
                break;
            }
        }

        if (!$token_raw) return "MISSING_TOKEN";

        $token = $token_raw;
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        // JWT Strategy
        try {
            $jwt_config = new \RestApi\Config\JWT();
            $key = preg_replace('/^["\']|["\']$/', '', trim($jwt_config->jwt_key));
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, $jwt_config->jwt_algorithm));
            
            if ($decoded && isset($decoded->id)) return (int)$decoded->id;
            if ($decoded && isset($decoded->crm_user_id)) return (int)$decoded->crm_user_id;
        } catch (\Exception $e) {}

        // DB Fallback
        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row && $user_row->id) return (int)$user_row->id;
        }

        return "INVALID_TOKEN";
    }

    public function update_profile() {
        $this->_init();
        $user_id = $this->_validate_user();
        if (!is_int($user_id)) {
            return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized: " . $user_id]);
        }

        $allowed_fields = [
            'first_name', 'last_name', 'job_title', 'phone', 'alternative_phone', 
            'address', 'alternative_address', 'gender', 'dob', 'skype', 
            'facebook', 'twitter', 'linkedin', 'personal_experience'
        ];

        $data = [];
        foreach ($allowed_fields as $field) {
            if (isset($this->request_data[$field])) {
                $data[$field] = $this->request_data[$field];
            }
        }

        if (empty($data)) {
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "No data to update."]);
        }

        if ($this->users_model->ci_save($data, $user_id)) {
            $updated_user = $this->users_model->get_one($user_id);
            return $this->response->setJSON([
                "success" => true, 
                "message" => "Profile updated successfully.",
                "user" => [
                    "id" => $updated_user->id,
                    "first_name" => $updated_user->first_name,
                    "last_name" => $updated_user->last_name,
                    "job_title" => $updated_user->job_title,
                    "phone" => $updated_user->phone,
                    "address" => $updated_user->address,
                    "image" => $updated_user->image,
                    "email" => $updated_user->email
                ]
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "Failed to update profile."]);
    }

    public function reset_password() {
        $this->_init();
        $user_id = $this->_validate_user();
        if (!is_int($user_id)) {
            return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized"]);
        }

        $current_password = $this->request_data['current_password'] ?? null;
        $new_password = $this->request_data['new_password'] ?? null;
        $confirm_password = $this->request_data['confirm_password'] ?? null;

        if (!$current_password || !$new_password || !$confirm_password) {
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "All password fields are required."]);
        }

        if ($current_password === $new_password) {
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "New password cannot be the same as current password."]);
        }

        if (strlen($new_password) < 6) {
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "Password must be at least 6 characters long."]);
        }

        if ($new_password !== $confirm_password) {
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "New password and confirmation do not match."]);
        }

        $user = $this->users_model->get_one($user_id);
        if (!(password_verify($current_password, $user->password) || md5($current_password) === $user->password)) {
            return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Current password is incorrect."]);
        }

        $data = ["password" => password_hash($new_password, PASSWORD_DEFAULT)];
        if ($this->users_model->ci_save($data, $user_id)) {
            return $this->response->setJSON(["success" => true, "message" => "Password reset successfully."]);
        }

        return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "Failed to reset password."]);
    }
    
    public function upload_avatar() {
        $this->_init();
        $user_id = $this->_validate_user();
        if (!is_int($user_id)) {
            return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized"]);
        }

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(["success" => false, "message" => "Invalid image file."]);
        }

        $image_file_name = $file->getTempName();
        $image_file_size = $file->getSize();
        $original_name = $file->getName();
        
        $profile_image_path = get_setting("profile_image_path");
        if (!$profile_image_path) {
            $profile_image_path = "files/profile_images/";
        }
        
        // Use native CI4 move for direct upload robustness
        $extension = $file->guessExtension() ?: "png";
        $new_filename = "avatar_" . uniqid() . "." . $extension;
        try {
            $file->move(FCPATH . $profile_image_path, $new_filename);
            $move_result = array("file_name" => $new_filename);
        } catch (\Exception $e) {
            $error_msg = "Error: Gagal memindahkan file ke " . $profile_image_path . ". " . $e->getMessage();
            return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => $error_msg]);
        }
        
        if (!$file->hasMoved()) {
            return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "Gagal memproses unggahan file."]);
        }

        $profile_image = serialize($move_result);

        $user_info = $this->users_model->get_one($user_id);
        
        // delete old file
        if ($user_info->image) {
            delete_app_files($profile_image_path, array(@unserialize($user_info->image)));
        }

        $image_data = array("image" => $profile_image);
        if ($this->users_model->ci_save($image_data, $user_id)) {
            $updated_user = $this->users_model->get_one($user_id);
            return $this->response->setJSON([
                "success" => true, 
                "message" => "Profile picture updated.",
                "image" => $updated_user->image
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "Failed to save image reference."]);
    }


    public function delete_avatar() {
        $this->_init();
        $user_id = $this->_validate_user();
        if (!is_int($user_id)) {
            return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized"]);
        }

        $user_info = $this->users_model->get_one($user_id);
        $profile_image_path = get_setting("profile_image_path");

        if ($user_info->image) {
            delete_app_files($profile_image_path, array(@unserialize($user_info->image)));
        }

        $image_data = array("image" => "");
        if ($this->users_model->ci_save($image_data, $user_id)) {
            return $this->response->setJSON([
                "success" => true, 
                "message" => "Profile picture removed.",
                "image" => ""
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON(["success" => false, "message" => "Failed to remove image reference."]);
    }

    /**
     * Verify user status (active/disable_login) real-time
     * Safe approach: checking inside plugin instead of core RestApi
     */
    public function verify_status() {
        $this->_init();
        $user_id = $this->_validate_user();
        
        if (!is_int($user_id)) {
            return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Sesi berakhir. Silakan login kembali."]);
        }

        $user_info = $this->users_model->get_one($user_id);

        if (!$user_info->id || $user_info->deleted == 1) {
            return $this->response->setStatusCode(403)->setJSON([
                "success" => false, 
                "status" => "blocked",
                "message" => "Akun tidak ditemukan."
            ]);
        }

        // Check Disable Login first
        if ($user_info->disable_login == 1) {
            return $this->response->setStatusCode(403)->setJSON([
                "success" => false, 
                "status" => "blocked",
                "message" => "Akun dinonaktifkan"
            ]);
        }

        // Check Inactive status
        if ($user_info->status !== 'active') {
            return $this->response->setStatusCode(403)->setJSON([
                "success" => false, 
                "status" => "blocked",
                "message" => "Anda sudah tidak menjadi pegawai aktif"
            ]);
        }

        return $this->respond([
            "success" => true, 
            "status" => "active",
            "user" => [
                "id" => $user_info->id,
                "email" => $user_info->email,
                "status" => $user_info->status
            ]
        ], 200);
    }
}