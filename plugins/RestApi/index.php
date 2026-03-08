<?php

//Prevent direct access
defined('PLUGINPATH') or exit('No direct script access allowed');

require_once __DIR__.'/vendor/autoload.php';

use RestApi\Libraries\Apiinit;

/*
  Plugin Name: API
  Description: Rest API module for RISE CRM
  Version: 1.3.0
  Requires at least: 2.8
  Author: Themesic Interactive
  Author URL: https://1.envato.market/themesic
 */



app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
	$sidebar_menu["API"] = [
		"name"     => "api",
		"url"      => "api_settings",
		"class"    => "tag",
		"position" => 6
	];

	return $sidebar_menu;
});

app_hooks()->add_filter('app_filter_app_csrf_exclude_uris', function ($urls) {
	Apiinit::check_url("RestApi");
	$urls[] = "api/*";
	return $urls;
});

app_hooks()->add_action("app_hook_data_insert", function ($data_info) {
    _rest_api_sync_staff_user($data_info);
});

app_hooks()->add_action("app_hook_data_update", function ($data_info) {
    _rest_api_sync_staff_user($data_info);
});

function _rest_api_sync_staff_user($data_info) {
    $table = get_array_value($data_info, "table_without_prefix");
    if ($table !== "users") {
        return;
    }

    $user_id = get_array_value($data_info, "id");
    $Users_model = model("App\Models\Users_model");
    $user_info = $Users_model->get_one($user_id);

    // Automate if user is staff
    if ($user_info && $user_info->user_type === "staff" && !$user_info->deleted) {
        $Api_settings_model = model("RestApi\Models\Api_settings_model");
        
        $existing = $Api_settings_model->get_one_where(array("user" => $user_info->email));
        
        if (!$existing || empty($existing->id)) {
            $api_data = array(
                "user" => $user_info->email,
                "name" => $user_info->first_name . " " . $user_info->last_name,
                "expiration_date" => date("Y-m-d H:i:s", strtotime("+1 year"))
            );
            $Api_settings_model->add($api_data);
        }
    }
}

register_installation_hook("RestApi", function ($item_purchase_code) {
		include PLUGINPATH . "RestApi/install/do_install.php";
});

register_uninstallation_hook("RestApi", function () {
    $dbprefix = get_db_prefix();
    $db = db_connect('default');

    $sql_query = "DELETE FROM `" . $dbprefix . "settings` WHERE `" . $dbprefix . "settings`.`setting_name`='RestApi_verification_id';";
    $db->query($sql_query);

    $sql_query = "DELETE FROM `" . $dbprefix . "settings` WHERE `" . $dbprefix . "settings`.`setting_name`='RestApi_verified';";
    $db->query($sql_query);

    $sql_query = "DELETE FROM `" . $dbprefix . "settings` WHERE `" . $dbprefix . "settings`.`setting_name`='RestApi_last_verification';";
    $db->query($sql_query);

    $sql_query = "DELETE FROM `" . $dbprefix . "settings` WHERE `" . $dbprefix . "settings`.`setting_name`='RestApi_version';";
    $db->query($sql_query);

});