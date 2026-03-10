<?php

namespace HaviaCMS\Controllers;

use App\Controllers\Security_Controller;

class User_management extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_admin();
    }

    function index() {
        return $this->template->rander("HaviaCMS\Views\user_management\index");
    }

    function modal_form() {
        $id = $this->request->getPost('id');
        $view_data['model_info'] = $this->Users_model->get_one($id);
        
        $roles = model("App\Models\Roles_model")->get_all_where(array("deleted" => 0))->getResult();
        $roles_dropdown = array("0" => "--- " . app_lang("role") . " ---");
        foreach ($roles as $role) {
            $roles_dropdown[$role->id] = $role->title;
        }
        $view_data['roles_dropdown'] = $roles_dropdown;

        return $this->template->view("HaviaCMS\Views\user_management\modal_form", $view_data);
    }

    function save() {
        $id = $this->request->getPost('id');
        
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "first_name" => "required",
            "last_name" => "required",
            "email" => "required|valid_email"
        ));

        $email = $this->request->getPost('email');

        // Skip email check if it's an update and email hasn't changed
        if ($id) {
            $current_user = $this->Users_model->get_one($id);
            if ($current_user->email != $email) {
                if ($this->Users_model->is_email_exists($email)) {
                    echo json_encode(array("success" => false, 'message' => app_lang('duplicate_email')));
                    exit();
                }
            }
        } else {
            if ($this->Users_model->is_email_exists($email)) {
                echo json_encode(array("success" => false, 'message' => app_lang('duplicate_email')));
                exit();
            }
        }

        $data = array(
            "first_name" => $this->request->getPost('first_name'),
            "last_name" => $this->request->getPost('last_name'),
            "email" => $email,
            "job_title" => $this->request->getPost('job_title'),
            "role_id" => $this->request->getPost('role_id'),
            "is_admin" => $this->request->getPost('is_admin') ? 1 : 0,
            "user_type" => "staff"
        );

        $password = $this->request->getPost('password');
        if ($password) {
            $data["password"] = password_hash($password, PASSWORD_DEFAULT);
        }

        $save_id = $this->Users_model->ci_save($data, $id);
        if ($save_id) {
            $data_info = $this->Users_model->get_details(array("id" => $save_id))->getRow();
            echo json_encode(array("success" => true, "data" => $this->_make_row($data_info), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function list_data() {
        $list_data = $this->Users_model->get_details(array("user_type" => "staff"))->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _make_row($data) {
        $image_url = get_avatar($data->image);
        $user_avatar = "<span class='avatar avatar-xs'><img src='$image_url' alt='...'></span>";

        return array(
            $user_avatar,
            $data->first_name . " " . $data->last_name,
            $data->email,
            $data->job_title,
            $data->role_title,
            modal_anchor(get_uri("user_management/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_staff'), "data-post-id" => $data->id))
        );
    }
}
