<?php

namespace App\Controllers;

class User_management extends Security_Controller {

    function __construct() {
        parent::__construct();
        // Only admin with is_admin = 1 can access
        if (!$this->login_user->is_admin) {
            app_redirect("forbidden");
        }
    }

    public function index() {
        return $this->template->rander("user_management/index");
    }

    /* list of team members, prepared for datatable  */
    public function list_data() {
        $options = array("user_type" => "staff");
        $list_data = $this->Users_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _make_row($data) {
        $image_url = get_avatar($data->image);
        $user_avatar = "<span class='avatar avatar-xs'><img src='$image_url' alt='...'></span>";
        $full_name = $data->first_name . " " . $data->last_name . " ";
        $role_title = $data->role_title ? $data->role_title : ($data->is_admin ? app_lang('admin') : app_lang('team_member'));

        return array(
            $user_avatar,
            modal_anchor(get_uri("user_management/modal_form"), $full_name, array("class" => "edit", "title" => app_lang('edit_user'), "data-post-id" => $data->id)),
            $data->email,
            $data->job_title,
            $role_title,
            modal_anchor(get_uri("user_management/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_user'), "data-post-id" => $data->id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_user'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("user_management/delete"), "data-action" => "delete-confirmation"))
        );
    }

    /* open the modal form of user management */
    public function modal_form() {
        $id = $this->request->getPost('id');
        $view_data['model_info'] = $this->Users_model->get_one($id);
        $view_data['roles_dropdown'] = array("" => "-") + $this->Roles_model->get_dropdown_list(array("title"), "id");

        return $this->template->view('user_management/modal_form', $view_data);
    }

    /* insert/update a user */
    public function save() {
        $id = $this->request->getPost('id');
        $this->validate_submitted_data(array(
            "email" => "required|valid_email",
            "first_name" => "required",
            "last_name" => "required"
        ));

        $email = $this->request->getPost('email');
        if ($this->Users_model->is_email_exists($email, $id)) {
            echo json_encode(array("success" => false, 'message' => app_lang('duplicate_email')));
            exit();
        }

        $user_data = array(
            "first_name" => $this->request->getPost('first_name'),
            "last_name" => $this->request->getPost('last_name'),
            "email" => $email,
            "job_title" => $this->request->getPost('job_title'),
            "role_id" => $this->request->getPost('role_id') ? $this->request->getPost('role_id') : 0,
            "is_admin" => $this->request->getPost('is_admin') ? 1 : 0,
            "user_type" => "staff",
        );

        if (!$id) {
            $user_data["created_at"] = get_current_utc_time();
            $password = $this->request->getPost('password');
            if ($password) {
                $user_data["password"] = password_hash($password, PASSWORD_DEFAULT);
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('password_required')));
                exit();
            }
        } else {
            $password = $this->request->getPost('password');
            if ($password) {
                $user_data["password"] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        $save_id = $this->Users_model->ci_save($user_data, $id);
        if ($save_id) {
            
            // --- SYNC WITH API MANAGEMENT START ---
            try {
                // Check if RestApi plugin model exists and is active
                if (file_exists(PLUGINPATH . "RestApi/Models/Api_settings_model.php")) {
                    $api_settings_model = model('RestApi\Models\Api_settings_model');
                    
                    $api_user = $api_settings_model->get_one_where(['user' => $email]);
                    
                    // If creating new or API counterpart doesn't exist
                    if (!$api_user || empty($api_user->id)) {
                        helper('jwt');
                        $payload = [
                            'id' => $save_id,
                            'email' => $email,
                            'user_type' => 'staff',
                            'is_admin' => $this->request->getPost('is_admin') ? 1 : 0
                        ];
                        // Generate a valid token
                        $token = EncodeJWTtoken($payload);
                        
                        $api_data = [
                            'user' => $email,
                            'name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
                            'token' => $token,
                            'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 year'))
                        ];
                        
                        $api_settings_model->ci_save($api_data);
                    } else {
                        // If updating info, just sync the name and email
                        $api_data = [
                            'name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
                            'user' => $email
                        ];
                        $api_settings_model->update_data($api_data, ['id' => $api_user->id]);
                    }
                }
            } catch (\Exception $ex) {
                // Fail silently if RestAPI plugin is missing/disabled to not crash main flow
            }
            // --- SYNC WITH API MANAGEMENT END ---

            $row_data = $this->Users_model->get_details(array("id" => $save_id))->getRow();
            if ($row_data) {
                echo json_encode(array("success" => true, "data" => $this->_make_row($row_data), 'id' => $save_id, 'message' => app_lang('record_saved')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
            }
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* delete a user */
    public function delete() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');
        if ($id != $this->login_user->id && $this->Users_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

}
