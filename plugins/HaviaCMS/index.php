<?php

/*
  Plugin Name: Havia CMS & API Sync
  Description: Custom CMS for Landing Page and Automatic API Token Sync for Mobile App.
  Version: 2.0.0
  Author: Havia Team
 */

//Prevent direct access
defined('PLUGINPATH') or exit('No direct script access allowed');

// ============================================================
// SIDEBAR MENU
// ============================================================
app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    if (isset($sidebar_menu["dashboard"])) {
        $ci = new \App\Controllers\Security_Controller(false);
        if (isset($ci->login_user->is_admin) && $ci->login_user->is_admin == 1) {
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

// Inject CSS to fix oval avatar without touching core files
app_hooks()->add_action("app_hook_head_extension", function () {
    echo '<style type="text/css">
        .avatar {
            aspect-ratio: 1 / 1 !important;
            overflow: hidden !important;
            display: inline-block !important;
        }
        .avatar img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 50% !important;
        }
    </style>';
});

// ============================================================
// AUTO-CREATE LANDING PAGE TABLES (safe, non-destructive)
// Called directly at plugin load time since app_hook_after_load doesn't exist in Rise CRM.
// ============================================================
havia_create_lp_tables();

function havia_create_lp_tables()
{
    static $checked = false;
    if ($checked)
        return;
    $checked = true;

    try {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();

        // ---------- 1. Hero Slides ----------
        $t = $prefix . "lp_hero_slides";
        $db->query("CREATE TABLE IF NOT EXISTS `$t` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `image` VARCHAR(500) DEFAULT NULL,
            `heading_h1` VARCHAR(255) DEFAULT NULL,
            `heading_h2` VARCHAR(255) DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `deleted` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ---------- 2. Project Categories ----------
        $t = $prefix . "lp_project_categories";
        $db->query("CREATE TABLE IF NOT EXISTS `$t` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `sort_order` INT DEFAULT 0,
            `is_default` TINYINT(1) DEFAULT 0,
            `deleted` TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Seed default categories if table is empty
        $count = $db->query("SELECT COUNT(*) as cnt FROM `$t`")->getRow()->cnt;
        if ($count == 0) {
            $defaults = ['Residential', 'Commercial', 'Educational', 'Interior', 'Masterplan'];
            foreach ($defaults as $i => $name) {
                $db->query("INSERT INTO `$t` (`name`, `sort_order`, `is_default`) VALUES ('$name', $i, 1)");
            }
        }

        // ---------- 3. Projects ----------
        $t = $prefix . "lp_projects";
        $db->query("CREATE TABLE IF NOT EXISTS `$t` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT NOT NULL,
            `title` VARCHAR(255) DEFAULT NULL,
            `location` VARCHAR(255) DEFAULT NULL,
            `year` VARCHAR(10) DEFAULT NULL,
            `client` VARCHAR(255) DEFAULT NULL,
            `scope` TEXT DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `deleted` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ---------- 4. Project Images ----------
        $t = $prefix . "lp_project_images";
        $db->query("CREATE TABLE IF NOT EXISTS `$t` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `image_path` VARCHAR(500) DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `deleted` TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ---------- 5. Portfolio Requests ----------
        $t = $prefix . "lp_portfolio_requests";
        $db->query("CREATE TABLE IF NOT EXISTS `$t` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) DEFAULT NULL,
            `contact` VARCHAR(255) DEFAULT NULL,
            `contact_type` VARCHAR(20) DEFAULT 'unknown',
            `interest` VARCHAR(255) DEFAULT NULL,
            `status` VARCHAR(20) DEFAULT 'pending',
            `admin_notes` TEXT DEFAULT NULL,
            `deleted` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $t = $prefix . "lp_team_members";
        $db->query("CREATE TABLE IF NOT EXISTS `$t` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) DEFAULT NULL,
            `job_title` VARCHAR(255) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `image` VARCHAR(500) DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `deleted` TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Add description column to existing team table if missing
        try {
            $db->query("ALTER TABLE `$t` ADD `description` TEXT DEFAULT NULL AFTER `job_title` ");
        } catch (\Exception $e) {
            // Ignore error if column already exists
        }

        // ---------- 7. Gallery ----------
        $t = $prefix . "lp_gallery";
        $db->query("CREATE TABLE IF NOT EXISTS `$t` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `image` VARCHAR(500) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `deleted` TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Add description column to existing gallery table if missing
        if (!$db->fieldExists('description', $t)) {
            $db->query("ALTER TABLE `$t` ADD `description` TEXT DEFAULT NULL AFTER `image`");
        }

        // ---------- 8. Testimonials ----------
        $t = $prefix . "lp_testimonials";
        $db->query("CREATE TABLE IF NOT EXISTS `$t` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `type` VARCHAR(20) DEFAULT 'corporate',
            `image` VARCHAR(500) DEFAULT NULL,
            `name` VARCHAR(255) DEFAULT NULL,
            `subtitle` VARCHAR(255) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `deleted` TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ---------- 9. Client Logos ----------
        $t = $prefix . "lp_client_logos";
        $db->query("CREATE TABLE IF NOT EXISTS `$t` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `image` VARCHAR(500) DEFAULT NULL,
            `name` VARCHAR(255) DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `deleted` TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ---------- Create upload directories ----------
        $upload_base = FCPATH . 'files' . DIRECTORY_SEPARATOR . 'lp_uploads' . DIRECTORY_SEPARATOR;
        $subdirs = ['hero', 'projects', 'team', 'gallery', 'testimonials', 'clients', 'about'];
        foreach ($subdirs as $dir) {
            $path = $upload_base . $dir;
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
        }

    } catch (\Exception $ex) {
        log_message('error', 'HaviaCMS table creation failed: ' . $ex->getMessage());
    }
}


// ============================================================
// API TOKEN SYNC (unchanged from v1)
// ============================================================

function havia_ensure_api_table_column($db, $api_settings_model)
{
    static $checked = false;
    if ($checked)
        return;

    $db_prefix = $db->getPrefix();
    $table_name = $db_prefix . "rise_api_users";

    $fields = $db->getFieldNames($table_name);
    if (!in_array('crm_user_id', $fields)) {
        $db->query("ALTER TABLE `$table_name` ADD `crm_user_id` INT(11) NULL AFTER `id` ");
    }
    $checked = true;
}

function havia_sync_api_token($data_info)
{
    $table = get_array_value($data_info, "table_without_prefix");
    if ($table !== "users") {
        return;
    }

    $user_id = get_array_value($data_info, "id");
    $Users_model = model("App\Models\Users_model");
    $user_info = $Users_model->get_one($user_id);

    if ($user_info && $user_info->user_type === "staff" && !$user_info->deleted) {
        try {
            if (file_exists(PLUGINPATH . "RestApi/Models/Api_settings_model.php")) {
                $api_settings_model = model('RestApi\Models\Api_settings_model');
                $db = \Config\Database::connect();

                havia_ensure_api_table_column($db, $api_settings_model);

                $api_user = $api_settings_model->get_one_where(['crm_user_id' => $user_id]);

                if (!$api_user || empty($api_user->id)) {
                    $api_user = $api_settings_model->get_one_where(['user' => $user_info->email]);
                }

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
                        'crm_user_id' => $user_id,
                        'user' => $user_info->email,
                        'name' => $user_info->first_name . ' ' . $user_info->last_name,
                        'token' => $token,
                        'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 year'))
                    ];

                    $api_settings_model->ci_save($api_data);
                } else {
                    $api_data = [
                        'crm_user_id' => $user_id,
                        'name' => $user_info->first_name . ' ' . $user_info->last_name,
                        'user' => $user_info->email
                    ];
                    $api_settings_model->ci_save($api_data, $api_user->id);
                }
            }
        } catch (\Exception $ex) {
            // Fail silently
        }
    }
}

function havia_delete_api_user($data_info)
{
    $table = get_array_value($data_info, "table_without_prefix");
    if ($table !== "users") {
        return;
    }

    $user_id = get_array_value($data_info, "id");

    try {
        if (file_exists(PLUGINPATH . "RestApi/Models/Api_settings_model.php")) {
            $api_settings_model = model('RestApi\Models\Api_settings_model');

            $api_user = $api_settings_model->get_one_where(['crm_user_id' => $user_id]);

            if (!$api_user || empty($api_user->id)) {
                $db = \Config\Database::connect();
                $builder = $db->table('users');
                $crm_user = $builder->getWhere(array("id" => $user_id))->getRow();
                if ($crm_user && $crm_user->email) {
                    $api_user = $api_settings_model->get_one_where(['user' => $crm_user->email]);
                }
            }

            if ($api_user && $api_user->id) {
                $api_settings_model->delete_data($api_user->id);
            }
        }
    } catch (\Exception $ex) {
        // Fail silently
    }
}
