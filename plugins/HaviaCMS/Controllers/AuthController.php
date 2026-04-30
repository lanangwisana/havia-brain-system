<?php

namespace HaviaCMS\Controllers;

use App\Controllers\App_Controller;

class AuthController extends App_Controller {

    function __construct() {
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
    }

    function login() {
        if ($this->request->getMethod() === "options") {
            return $this->response->setJSON(["status" => "ok"]);
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        if (!$email || !$password) {
            return $this->response->setJSON(["success" => false, "message" => "Email and password are required."]);
        }

        // First, found user by email only (including deleted check for security)
        $user = $this->Users_model->get_one_where([
            'email' => $email, 
            'deleted' => 0
        ]);

        if (!$user->id) {
            return $this->response->setJSON(["success" => false, "message" => "Invalid credentials."]);
        }

        // Check if account is specifically disabled
        if ($user->disable_login == 1) {
            return $this->response->setJSON(["success" => false, "message" => "Akun dinonaktifkan"]);
        }

        // Check if member is inactive
        if ($user->status !== 'active') {
            return $this->response->setJSON(["success" => false, "message" => "Anda sudah tidak menjadi pegawai aktif"]);
        }

        // Final check: Password
        if (password_verify($password, $user->password) || md5($password) === $user->password) {
            // Success
            $api_settings_model = model('RestApi\Models\Api_settings_model');
            $api_user = $api_settings_model->get_one_where(['user' => $email]);

            if ($api_user->id) {
                // Build permissions for mobile
                $role_title_val = '';
                if ($user->is_admin) {
                    $permissions_array = ["_all_access" => true];
                    $role_title_val = 'Admin';
                } else if ($user->role_id) {
                    $roles_model = model('App\Models\Roles_model');
                    $role = $roles_model->get_one($user->role_id);
                    if ($role && $role->permissions) {
                        $raw = @unserialize($role->permissions);
                        if (is_array($raw)) {
                            $permissions_array = $raw;
                        }
                    }
                    if ($role && isset($role->title)) {
                        $role_title_val = $role->title;
                    }
                }
                
                $permissions_array['role_title'] = $role_title_val;
                $permissions_array['role_id'] = $user->role_id;

                return $this->response->setJSON([
                    "success" => true,
                    "token" => $api_user->token,
                    "user" => [
                        "id" => $user->id,
                        "first_name" => $user->first_name,
                        "last_name" => $user->last_name,
                        "name" => $user->first_name . " " . $user->last_name,
                        "email" => $user->email,
                        "is_admin" => $user->is_admin,
                        "user_type" => $user->user_type,
                        "role_id" => $user->role_id,
                        "job_title" => $user->job_title,
                        "image" => $user->image,
                        "permissions" => $permissions_array
                    ]
                ]);
            } else {
                return $this->response->setJSON(["success" => false, "message" => "API token not found."]);
            }
        }

        return $this->response->setJSON(["success" => false, "message" => "Invalid credentials."]);
    }

    function register() {
        if ($this->request->getMethod() === "options") {
            return $this->response->setJSON(["status" => "ok"]);
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $first_name = $this->request->getPost('first_name');
        $last_name = $this->request->getPost('last_name');

        if (!$email || !$password) {
            return $this->response->setJSON(["success" => false, "message" => "Required fields missing."]);
        }

        if ($this->Users_model->is_email_exists($email)) {
            return $this->response->setJSON(["success" => false, "message" => "Email already exists."]);
        }

        $data = [
            "email" => $email,
            "password" => password_hash($password, PASSWORD_DEFAULT),
            "first_name" => $first_name,
            "last_name" => $last_name,
            "user_type" => "staff",
            "created_at" => date("Y-m-d H:i:s")
        ];

        $save_id = $this->Users_model->ci_save($data);
        if ($save_id) {
            return $this->response->setJSON(["success" => true, "message" => "User registered successfully."]);
        }

        return $this->response->setJSON(["success" => false, "message" => "Registration failed."]);
    }
}