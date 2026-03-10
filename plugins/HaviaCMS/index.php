<?php

/*
  Plugin Name: Havia CMS & API Sync
  Description: Custom CMS for Landing Page and Automatic API Token Sync for Mobile App.
  Version: 1.0.0
  Author: Havia Team
 */

//Prevent direct access
defined('PLUGINPATH') or exit('No direct script access allowed');

// Add Landingpage CMS menu to Sidebar
app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    if (isset($sidebar_menu["dashboard"])) {
        $sidebar_menu["havia_cms_menu"] = array(
            "name" => "havia_cms",
            "url" => "landingpage_cms",
            "class" => "layout",
            "position" => 10
        );
        
        $sidebar_menu["havia_user_mgmt"] = array(
            "name" => "user_management",
            "url" => "user_management",
            "class" => "user-check",
            "position" => 11
        );
    }

    return $sidebar_menu;
});

// Sync API Token when user is saved/updated
app_hooks()->add_action("app_hook_data_insert", "havia_sync_api_token");
app_hooks()->add_action("app_hook_data_update", "havia_sync_api_token");

function havia_sync_api_token($data_info) {
    $table = get_array_value($data_info, "table_without_prefix");
    if ($table !== "users") {
        return;
    }

    $user_id = get_array_value($data_info, "id");
    $Users_model = model("App\Models\Users_model");
    $user_info = $Users_model->get_one($user_id);

    // Sync only for staff type
    if ($user_info && $user_info->user_type === "staff" && !$user_info->deleted) {
        try {
            if (file_exists(PLUGINPATH . "RestApi/Models/Api_settings_model.php")) {
                $api_settings_model = model('RestApi\Models\Api_settings_model');
                
                $api_user = $api_settings_model->get_one_where(['user' => $user_info->email]);
                
                // Load JWT helper from RestApi plugin
                if (file_exists(PLUGINPATH . "RestApi/Helpers/jwt_helper.php")) {
                    require_once PLUGINPATH . "RestApi/Helpers/jwt_helper.php";
                }

                if (!$api_user || empty($api_user->id)) {
                    $payload = [
                        'id' => $user_id,
                        'email' => $user_info->email,
                        'user_type' => 'staff',
                        'is_admin' => $user_info->is_admin
                    ];
                    
                    $token = "";
                    if (function_exists('EncodeJWTtoken')) {
                        $token = EncodeJWTtoken($payload);
                    }
                    
                    $api_data = [
                        'user' => $user_info->email,
                        'name' => $user_info->first_name . ' ' . $user_info->last_name,
                        'token' => $token,
                        'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 year'))
                    ];
                    
                    $api_settings_model->ci_save($api_data);
                } else {
                    $api_data = [
                        'name' => $user_info->first_name . ' ' . $user_info->last_name,
                        'user' => $user_info->email
                    ];
                    $api_settings_model->update_data($api_data, ['id' => $api_user->id]);
                }
            }
        } catch (\Exception $ex) {
            // Fail silently
        }
    }
}
