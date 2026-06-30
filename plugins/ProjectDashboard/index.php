<?php

/*
  Plugin Name: Project Dashboard & S-Curve Automation
  Description: Advanced project tracking with automated S-Curve, RAB-based weighting, and weekly progress monitoring.
  Version: 1.0.0
  Author: Havia Team
 */

//Prevent direct access
defined('PLUGINPATH') or exit('No direct script access allowed');

// ============================================================
// ROUTE REGISTRATION (langsung di index.php agar pasti ter-load)
// ============================================================
$routes = \Config\Services::routes();
$routes->group('project_dashboard', ['namespace' => 'ProjectDashboard\Controllers'], function ($routes) {
    $routes->get('/', 'Project_dashboard::index');
    $routes->get('index', 'Project_dashboard::index');
    $routes->get('view/(:any)', 'Project_dashboard::view/$1');
    $routes->get('activity_log/(:any)', 'Project_dashboard::activity_log/$1');
    $routes->post('delete_weight', 'Project_dashboard::delete_weight');
    $routes->get('modal_edit_rab', 'Project_dashboard::modal_edit_rab');
    $routes->post('modal_edit_rab', 'Project_dashboard::modal_edit_rab');
    $routes->post('save_rab_weight', 'Project_dashboard::save_rab_weight');
    $routes->get('modal_edit_parent_dates', 'Project_dashboard::modal_edit_parent_dates');
    $routes->post('modal_edit_parent_dates', 'Project_dashboard::modal_edit_parent_dates');
    $routes->post('save_parent_dates', 'Project_dashboard::save_parent_dates');
    $routes->post('approve_rab', 'Project_dashboard::approve_rab');
    $routes->post('reject_rab', 'Project_dashboard::reject_rab');
});

// ============================================================
// SIDEBAR MENU
// ============================================================
app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    $ci = new \App\Controllers\Security_Controller(false);

    if (isset($ci->login_user->id)) {
        $is_admin = isset($ci->login_user->is_admin) && $ci->login_user->is_admin == 1;
        $do_not_show_projects = isset($ci->login_user->permissions) && get_array_value($ci->login_user->permissions, "do_not_show_projects") == "1";

        if ($is_admin || !$do_not_show_projects) {
            $sidebar_menu["project_dashboard"] = array(
                "name" => "project_dashboard",
                "url" => "project_dashboard",
                "class" => "trending-up", // Icon class (feather icon)
                "position" => 9 // Position it near Projects
            );
        }
    }

    return $sidebar_menu;
});

// ============================================================
// LANGUAGE STRINGS
// ============================================================
// We'll add custom language strings for our plugin
app_hooks()->add_filter('app_filter_custom_lang_values', function ($custom_lang) {
    $custom_lang["project_dashboard"] = "Project Dashboard";
    return $custom_lang;
});

// ============================================================
// CSRF EXCLUDE URIS
// ============================================================
app_hooks()->add_filter('app_filter_app_csrf_exclude_uris', function ($urls) {
    $urls[] = "project_dashboard/*";
    return $urls;
});

// ============================================================
// DATABASE INITIALIZATION
// ============================================================
// Function to auto-zero parent task RAB weight
function project_dashboard_zero_parent_weight($task_data) {
    log_message('error', 'project_dashboard_zero_parent_weight triggered. Task Data: ' . json_encode($task_data));
    if (isset($task_data['parent_task_id']) && $task_data['parent_task_id'] > 0) {
        $parent_task_id = $task_data['parent_task_id'];
        $Project_weights_model = model("ProjectDashboard\Models\Project_weights_model");
        
        $weight_info = $Project_weights_model->get_one_where(array("task_id" => $parent_task_id, "deleted" => 0));
        if ($weight_info && $weight_info->id && $weight_info->nominal_rab > 0) {
            // Set nominal_rab to 0 and weight_percentage to 0, clear dates
            log_message('error', 'Zeroing out parent task RAB. Parent Task ID: ' . $parent_task_id);
            $update_data = array("nominal_rab" => 0, "weight_percentage" => 0, "start_date" => null, "end_date" => null);
            $Project_weights_model->ci_save($update_data, $weight_info->id);
            
            // Recalculate weights
            $project_id = isset($task_data['project_id']) ? $task_data['project_id'] : $weight_info->project_id;
            log_message('error', 'Recalculating project ID: ' . $project_id);
            
            $Projects_model = model("App\Models\Projects_model");
            $project_info = $Projects_model->get_one($project_id);
            $weights = $Project_weights_model->get_details(array("project_id" => $project_id))->getResult();
            
            $total_rab = 0;
            foreach ($weights as $w) {
                $total_rab += isset($w->nominal_rab) ? (float) $w->nominal_rab : 0;
            }
            
            $project_price = (isset($project_info->price) && (float) $project_info->price > 0) ? (float) $project_info->price : $total_rab;
            
            if ($project_price > 0) {
                foreach ($weights as $w) {
                    $nom = isset($w->nominal_rab) ? (float) $w->nominal_rab : 0;
                    $percentage = ($nom / $project_price) * 100;
                    $update_data = array("weight_percentage" => $percentage);
                    $Project_weights_model->ci_save($update_data, $w->id);
                }
            }
        }
    }
}

app_hooks()->add_action('app_hook_data_insert', function ($data) {
    if (isset($data['table_without_prefix']) && $data['table_without_prefix'] === 'tasks') {
        project_dashboard_zero_parent_weight($data['data']);
    }
});

app_hooks()->add_action('app_hook_data_update', function ($data) {
    if (isset($data['table_without_prefix']) && $data['table_without_prefix'] === 'tasks') {
        project_dashboard_zero_parent_weight($data['data']);
    }
});

// Function to create tables if they don't exist
function project_detail_init_database()
{
    try {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();

        // 1. Project Weighting (derived from RAB)
        $t_weight = $prefix . "pd_project_weights";
        $db->query("CREATE TABLE IF NOT EXISTS `$t_weight` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `item_name` VARCHAR(255) NOT NULL,
            `nominal_rab` DECIMAL(20,2) DEFAULT 0.00,
            `weight_percentage` DECIMAL(10,4) DEFAULT 0.0000,
            `task_ids` TEXT DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `deleted` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Add task_ids if not exists (for existing tables)
        if (!$db->fieldExists('task_ids', $t_weight)) {
            $db->query("ALTER TABLE `$t_weight` ADD `task_ids` TEXT DEFAULT NULL AFTER `weight_percentage` ");
        }

        // Add task_id, start_date, and end_date for task-level automated planning
        if (!$db->fieldExists('task_id', $t_weight)) {
            $db->query("ALTER TABLE `$t_weight` ADD `task_id` INT DEFAULT 0 AFTER `project_id` ");
        }
        if (!$db->fieldExists('start_date', $t_weight)) {
            $db->query("ALTER TABLE `$t_weight` ADD `start_date` DATE DEFAULT NULL AFTER `nominal_rab` ");
        }
        if (!$db->fieldExists('end_date', $t_weight)) {
            $db->query("ALTER TABLE `$t_weight` ADD `end_date` DATE DEFAULT NULL AFTER `start_date` ");
        }
        if (!$db->fieldExists('weekly_weights', $t_weight)) {
            $db->query("ALTER TABLE `$t_weight` ADD `weekly_weights` TEXT DEFAULT NULL AFTER `weight_percentage` ");
        }
        if (!$db->fieldExists('approval_status', $t_weight)) {
            $db->query("ALTER TABLE `$t_weight` ADD `approval_status` VARCHAR(20) DEFAULT 'Approved' AFTER `weekly_weights` ");
        }
        if (!$db->fieldExists('pending_weekly_weights', $t_weight)) {
            $db->query("ALTER TABLE `$t_weight` ADD `pending_weekly_weights` TEXT DEFAULT NULL AFTER `approval_status` ");
        }
        if (!$db->fieldExists('pending_nominal_rab', $t_weight)) {
            $db->query("ALTER TABLE `$t_weight` ADD `pending_nominal_rab` DECIMAL(20,2) DEFAULT NULL AFTER `pending_weekly_weights` ");
        }
        if (!$db->fieldExists('reject_reason', $t_weight)) {
            $db->query("ALTER TABLE `$t_weight` ADD `reject_reason` TEXT DEFAULT NULL AFTER `pending_nominal_rab` ");
        }

        // 2. Weekly Progress Schedule (Planned)
        $t_schedule = $prefix . "pd_weekly_schedules";
        $db->query("CREATE TABLE IF NOT EXISTS `$t_schedule` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `week_number` INT NOT NULL,
            `planned_percentage` DECIMAL(10,4) DEFAULT 0.0000,
            `cumulative_planned` DECIMAL(10,4) DEFAULT 0.0000,
            `deleted` TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 3. Actual Weekly Progress (Realization)
        $t_actual = $prefix . "pd_weekly_actuals";
        $db->query("CREATE TABLE IF NOT EXISTS `$t_actual` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `week_number` INT NOT NULL,
            `actual_percentage` DECIMAL(10,4) DEFAULT 0.0000,
            `cumulative_actual` DECIMAL(10,4) DEFAULT 0.0000,
            `deviation` DECIMAL(10,4) DEFAULT 0.0000,
            `notes` TEXT,
            `deleted` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 4. Actual Progress Activity Logs
        $t_logs = $prefix . "pd_actual_activity_logs";
        $db->query("CREATE TABLE IF NOT EXISTS `$t_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `task_id` INT NOT NULL,
            `task_title` VARCHAR(255) NOT NULL,
            `week_number` INT NOT NULL,
            `old_actual` DECIMAL(10,4) DEFAULT 0.0000,
            `new_actual` DECIMAL(10,4) DEFAULT 0.0000,
            `created_by` INT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    } catch (\Exception $ex) {
        log_message('error', 'ProjectDetail table creation failed: ' . $ex->getMessage());
    }
}

// Initialize database
project_detail_init_database();