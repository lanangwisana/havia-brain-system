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

app_hooks()->add_filter('app_filter_app_csrf_exclude_uris', function ($urls) {
    $urls[] = "api/haviacms/*";
    return $urls;
});

app_hooks()->add_action("app_hook_data_insert", "havia_sync_api_token");
app_hooks()->add_action("app_hook_data_update", "havia_sync_api_token");
app_hooks()->add_action("app_hook_data_delete", "havia_delete_api_user");

/**
 * Ensures the API table has the crm_user_id column for robust syncing
 */
function havia_ensure_api_table_column($db, $api_settings_model) {
    static $checked = false;
    if ($checked) return;
    
    // On this specific server, the table is rise_rise_api_users because of double prefixing
    // We target the table defined in the model
    $db_prefix = $db->getPrefix();
    $table_name = $db_prefix . "rise_api_users"; 
    
    $fields = $db->getFieldNames($table_name);
    if (!in_array('crm_user_id', $fields)) {
        $db->query("ALTER TABLE `$table_name` ADD `crm_user_id` INT(11) NULL AFTER `id` ");
    }
    $checked = true;
}

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
                $db = \Config\Database::connect();
                
                // Ensure table is ready
                havia_ensure_api_table_column($db, $api_settings_model);
                
                // 1. Try search by crm_user_id (Best way to handle email changes)
                $api_user = $api_settings_model->get_one_where(['crm_user_id' => $user_id]);
                
                // 2. If not found, try search by current email (Internal sync/legacy)
                if (!$api_user || empty($api_user->id)) {
                    $api_user = $api_settings_model->get_one_where(['user' => $user_info->email]);
                }
                
                // Load JWT helper from RestApi plugin
                if (file_exists(PLUGINPATH . "RestApi/Helpers/jwt_helper.php")) {
                    require_once PLUGINPATH . "RestApi/Helpers/jwt_helper.php";
                }

                if (!$api_user || empty($api_user->id)) {
                    // CREATE NEW
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
                        'crm_user_id' => $user_id,
                        'user' => $user_info->email,
                        'name' => $user_info->first_name . ' ' . $user_info->last_name,
                        'token' => $token,
                        'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 year'))
                    ];
                    
                    $api_settings_model->ci_save($api_data);
                } else {
                    // UPDATE EXISTING
                    $api_data = [
                        'crm_user_id' => $user_id, // Ensure it's set
                        'name' => $user_info->first_name . ' ' . $user_info->last_name,
                        'user' => $user_info->email // This updates the email in API table if changed in CRM
                    ];
                    $api_settings_model->ci_save($api_data, $api_user->id);
                }
            }
        } catch (\Exception $ex) {
            // Fail silently
        }
    }
}

function havia_delete_api_user($data_info) {
    $table = get_array_value($data_info, "table_without_prefix");
    if ($table !== "users") {
        return;
    }

    $user_id = get_array_value($data_info, "id");
    
    try {
        if (file_exists(PLUGINPATH . "RestApi/Models/Api_settings_model.php")) {
            $api_settings_model = model('RestApi\Models\Api_settings_model');
            
            // 1. Search by crm_user_id for precision
            $api_user = $api_settings_model->get_one_where(['crm_user_id' => $user_id]);
            
            // 2. Fallback: Search by email if crm_user_id wasn't set yet (for legacy records)
            if (!$api_user || empty($api_user->id)) {
                $db = \Config\Database::connect();
                $builder = $db->table('users');
                $crm_user = $builder->getWhere(array("id" => $user_id))->getRow();
                if ($crm_user && $crm_user->email) {
                    $api_user = $api_settings_model->get_one_where(['user' => $crm_user->email]);
                }
            }
            
            if ($api_user && $api_user->id) {
                // Perform HARD DELETE specifically for API table
                $api_settings_model->delete_data($api_user->id);
            }
        }
    } catch (\Exception $ex) {
        // Fail silently
    }
}
