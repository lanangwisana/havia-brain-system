<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class FinanceApi extends ResourceController
{
    use ResponseTrait;

    protected $format = 'json';
    protected $projects_model;
    protected $expenses_model;
    protected $invoices_model;
    protected $invoice_payments_table;
    protected $users_model;
    protected $project_members_model;
    protected $api_settings_model;
    protected $settings_model;
    protected $db;
    private $initialized = false;

    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, authtoken, Accept");

        if (strtoupper(request()->getMethod()) === 'OPTIONS') {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

    private function _init()
    {
        if ($this->initialized)
            return;

        helper(['date_time', 'general', 'url']);

        if (defined('PLUGINPATH') && file_exists(PLUGINPATH . "RestApi/Helpers/jwt_helper.php")) {
            require_once PLUGINPATH . "RestApi/Helpers/jwt_helper.php";
        }

        $this->projects_model = model('App\Models\Projects_model');
        $this->expenses_model = model('App\Models\Expenses_model');
        $this->invoices_model = model('App\Models\Invoices_model');
        $this->users_model = model('App\Models\Users_model');
        $this->project_members_model = model('App\Models\Project_members_model');
        $this->api_settings_model = model('RestApi\Models\Api_settings_model');
        $this->settings_model = model('App\Models\Settings_model');

        $this->db = \Config\Database::connect();
        $this->invoice_payments_table = $this->db->prefixTable('invoice_payments');

        $this->initialized = true;
    }

    private function _validate_user()
    {
        $token_raw = null;
        $all_headers = $this->request->getHeaders();

        foreach ($all_headers as $name => $header) {
            if (strtolower($name) === 'authtoken' || strtolower($name) === 'authorization') {
                $token_raw = (string) $header;
                break;
            }
        }

        if (!$token_raw)
            return "ERROR_MISSING_HEADER";

        $token = $token_raw;
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        if (empty($token))
            return "ERROR_EMPTY_TOKEN";

        try {
            $jwt_config = new \RestApi\Config\JWT();
            $key = preg_replace('/^["\']|["\']$/', '', trim($jwt_config->jwt_key));
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, $jwt_config->jwt_algorithm));

            if ($decoded && is_object($decoded)) {
                $user_id = $decoded->id ?? $decoded->crm_user_id ?? null;
                if ($user_id) {
                    $user = $this->users_model->get_one($user_id);
                    if ($user && $user->id && !$user->deleted)
                        return (int) $user_id;
                }
            }
        } catch (\Exception $e) {
        }

        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row && $user_row->id)
                return (int) $user_row->id;
        }

        return "ERROR_AUTH_FAILED";
    }

    public function summary()
    {
        try {
            $this->_init();
            $validation = $this->_validate_user();
            if (!is_int($validation))
                return $this->failUnauthorized($validation);

            $user_id = $validation;
            $user = $this->users_model->get_one($user_id);

            // Check if user has permission to manage all projects (PM role)
            // PM detected via: expense permission = "all" OR can_manage_all_projects = 1
            $can_see_all_projects = (bool)$user->is_admin;
            $job_title = strtolower($user->job_title ?? '');
            $roles_model = model('App\Models\Roles_model');
            $role_title = '';

            if (!$can_see_all_projects && $user->role_id) {
                $role = $roles_model->get_one($user->role_id);
                $role_title = strtolower($role->title ?? '');
                if ($role && $role->permissions) {
                    $perms = @unserialize($role->permissions);
                    if (is_array($perms)) {
                        if (!empty($perms['can_manage_all_projects']) || 
                            (isset($perms['expense']) && $perms['expense'] === 'all')) {
                            $can_see_all_projects = true;
                        }
                    }
                }
            }

            // Explicit Role checks with broader keywords (Indonesian & English support)
            $is_admin_role = $user->is_admin || stripos($job_title, 'admin') !== false || stripos($role_title, 'admin') !== false;
            
            // PM keywords: "Project Manager", "Projek Manager", or just "PM"
            $is_pm = $can_see_all_projects || $is_admin_role || 
                     stripos($job_title, 'project manager') !== false || stripos($role_title, 'project manager') !== false ||
                     stripos($job_title, 'projek manager') !== false || stripos($role_title, 'projek manager') !== false ||
                     trim(strtolower($job_title)) === 'pm' || trim(strtolower($role_title)) === 'pm';

            $is_hr_admin_marketing = $is_admin_role || stripos($job_title, 'hr') !== false || stripos($role_title, 'hr') !== false || stripos($job_title, 'marketing') !== false || stripos($role_title, 'marketing') !== false;
            
            $is_restricted = stripos($job_title, 'arsitektur') !== false || stripos($role_title, 'arsitektur') !== false || stripos($job_title, 'drafter') !== false || stripos($role_title, 'drafter') !== false || stripos($job_title, 'estimator') !== false || stripos($role_title, 'estimator') !== false || stripos($job_title, 'ob') !== false || stripos($role_title, 'ob') !== false || stripos($job_title, 'office boy') !== false || stripos($role_title, 'office boy') !== false;

            if ($is_restricted && !$user->is_admin) {
                return $this->respond(["success" => true, "data" => []]);
            }

            // 1. Get projects.
            if ($is_admin_role || $user->is_admin || $is_pm) {
                // FORCE GLOBAL ACCESS: Bypass core model filters to get all 14+ projects for Admin/PM
                $projects = $this->db->table('projects')
                    ->select('projects.*, project_status.title AS status_title')
                    ->join('project_status', 'project_status.id = projects.status_id', 'left')
                    ->where('projects.deleted', 0)
                    ->get()->getResultArray();
            } else {
                $options = array();
                if ($user->user_type === "client") {
                    $options["client_id"] = $user->client_id;
                } else if (!$user->is_admin && !$is_pm && !$is_admin_role && !$is_hr_admin_marketing) {
                    $options["user_id"] = $user_id;
                }
                $projects = $this->projects_model->get_details($options)->getResultArray();
            }

            // Optimization: Get ALL expenses for these projects in one go to avoid N+1 performance bottlenecks
            $project_ids = array_column($projects, 'id');
            if (empty($project_ids)) $project_ids = [0];
            
            $all_expenses = $this->db->table('expenses')
                ->whereIn('project_id', $project_ids)
                ->where('deleted', 0)
                ->get()->getResultArray();

            $overall_total_budget = 0;
            $overall_total_balance = 0;
            $summary_data = [];

            foreach ($projects as $project) {
                // A. Filter Status: Skip projects with 'Completed' or 'Canceled' status
                $status_title = strtolower(trim($project['status_title'] ?? ''));
                if ($status_title === 'completed' || $status_title === 'canceled') {
                    continue;
                }

                $project_id = $project['id'];

                // SMART CURRENCY SANITIZER: Handle "Rp 1.875.000.000,00" or "1.875.000.000" formats
                $raw_price = (string) ($project['price'] ?? '0');
                $clean_price = preg_replace('/[^\d,]/', '', $raw_price); // Remove everything except digits and comma
                if (strpos($clean_price, ',') !== false) {
                    $parts = explode(',', $clean_price);
                    $project_price = (float) $parts[0]; // Take main amount
                } else {
                    $project_price = (float) $clean_price;
                }

                // B. Filter expenses for this specific project from pre-loaded pool
                $project_expenses = array_filter($all_expenses, function($e) use ($project_id) {
                    return $e['project_id'] == $project_id;
                });
                
                $total_expense = 0;
                $expense_count = 0;
                $expense_titles = [];
                $user_created_approved_expense = false;

                foreach ($project_expenses as $exp) {
                    // Only count specific categories if needed, otherwise check approval
                    $cat_title = strtolower($exp['category_title'] ?? '');
                    if (strpos($cat_title, 'project expense') === false && $exp['category_id'] != 2) {
                        continue;
                    }

                    // Check for "Approved" or "Approval" in custom fields
                    $cf_query = $this->db->table('custom_field_values')
                                         ->where(['related_to_type' => 'expenses', 'related_to_id' => $exp['id']])
                                         ->get()->getResult();
                    $is_approved = false;
                    foreach ($cf_query as $cf) {
                        $v_lower = strtolower(trim((string)$cf->value));
                        if ($v_lower === 'approved' || $v_lower === 'approval') {
                            $is_approved = true;
                            break;
                        }
                    }

                    if ($is_approved) {
                        $amt = (float) $exp['amount'];
                        $tax = (float) ($exp['tax_percentage'] ?? 0) / 100 * $amt;
                        $tax2 = (float) ($exp['tax_percentage2'] ?? 0) / 100 * $amt;

                        $total_expense += ($amt + $tax + $tax2);
                        $expense_count++;
                        $expense_titles[] = $exp['title'];

                        if ($exp['user_id'] == $user_id || (isset($exp['created_by']) && $exp['created_by'] == $user_id)) {
                            $user_created_approved_expense = true;
                        }
                    }
                }

                // C. Calculate Balance and Progress
                $balance = $project_price - $total_expense;
                $progress = 0;
                if (isset($project['total_points']) && $project['total_points'] > 0) {
                    $progress = round(($project['completed_points'] / $project['total_points']) * 100);
                } else if (isset($project['total_tasks']) && $project['total_tasks'] > 0) {
                    $progress = round(($project['completed_tasks'] / $project['total_tasks']) * 100);
                }

                // D. Determine Visibility (PIC check for RBAC compliance)
                $is_pic = false;
                if ($user->is_admin || $is_pm || $user->user_type === "client") {
                    $is_pic = true;
                } else if ($is_hr_admin_marketing) {
                    if ($user_created_approved_expense) $is_pic = true;
                } else {
                    $member_row = $this->db->table('project_members')
                        ->where(['user_id' => $user_id, 'project_id' => $project_id, 'is_leader' => 1, 'deleted' => 0])
                        ->get()->getRow();
                    
                    if ($member_row) {
                        $is_pic = true;
                    } else {
                        $tasks_model = model('App\Models\Tasks_model');
                        $pic_task = $tasks_model->get_details(['project_id' => $project_id, 'assigned_to' => $user_id, 'status' => 'all'])->getRow();
                        if ($pic_task) $is_pic = true;
                    }
                }

                // E. Accumulate and Add to Summary
                if ($is_pic) {
                    $overall_total_budget += $project_price;
                    $overall_total_balance += $balance;

                    $summary_data[] = [
                        'project_id' => $project_id,
                        'project_title' => $project['title'],
                        'project_price' => $project_price,
                        'total_expense' => $total_expense,
                        'balance' => $balance,
                        'progress' => $progress,
                        'expense_ratio' => $project_price > 0 ? round(($total_expense / $project_price) * 100, 2) : 0,
                        'status_title' => $project['status_title'] ?? 'Open',
                        'is_pic' => $is_pic,
                        'expense_count' => $expense_count,
                        'expense_titles' => implode(', ', array_slice($expense_titles, 0, 3)) . (count($expense_titles) > 3 ? '...' : '')
                    ];
                }
            }

            // 2. Pagination and Response
            usort($summary_data, function ($a, $b) {
                return (int) $b['project_id'] - (int) $a['project_id'];
            });

            $page = (int) $this->request->getGet('page');
            if ($page < 1) $page = 1;
            $limit = 5;
            $total_items = count($summary_data);
            $total_pages = ceil($total_items / $limit);
            $offset = ($page - 1) * $limit;
            $paginated_data = array_slice($summary_data, $offset, $limit);

            return $this->respond([
                "success" => true,
                "totals" => [
                    "total_budget" => $overall_total_budget,
                    "total_balance" => $overall_total_balance
                ],
                "debug" => [
                    "is_admin" => (bool)$user->is_admin,
                    "is_pm" => $is_pm,
                    "job_title" => $job_title,
                    "role_title" => $role_title,
                    "project_count" => count($projects)
                ],
                "data" => $paginated_data,
                "meta" => [
                    "total_items" => $total_items,
                    "total_pages" => $total_pages,
                    "current_page" => $page,
                    "per_page" => $limit
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function salaries()
    {
        try {
            $this->_init();
            $validation = $this->_validate_user();
            if (!is_int($validation))
                return $this->failUnauthorized($validation);

            $user_id = $validation;
            $user = $this->users_model->get_one($user_id);
            $job_title = strtolower($user->job_title ?? '');
            $roles_model = model('App\Models\Roles_model');
            $role_title = '';

            if (!$user->is_admin && $user->role_id) {
                $role = $roles_model->get_one($user->role_id);
                $role_title = strtolower($role->title ?? '');
            }

            $can_manage_all = (bool)$user->is_admin;
            if (!$can_manage_all && $user->role_id && isset($role)) {
                if ($role->permissions) {
                    $perms = @unserialize($role->permissions);
                    if (is_array($perms) && (!empty($perms['can_manage_all_projects']) || (isset($perms['expense']) && $perms['expense'] === 'all'))) {
                        $can_manage_all = true;
                    }
                }
            }

            $is_pm = $can_manage_all || strpos($job_title, 'project manager') !== false || strpos($role_title, 'project manager') !== false;
            $is_hr_admin_marketing = strpos($job_title, 'hr') !== false || strpos($role_title, 'hr') !== false || strpos($job_title, 'admin projek') !== false || strpos($role_title, 'admin projek') !== false || strpos($job_title, 'admin project') !== false || strpos($role_title, 'admin project') !== false || strpos($job_title, 'marketing') !== false || strpos($role_title, 'marketing') !== false;

            // 1. Fetch all expenses because we need to filter them manually based on category and custom field
            $options = [];
            $expenses = $this->expenses_model->get_details($options)->getResultArray();
            $salaries = [];

            $custom_field_values_model = model('App\Models\Custom_field_values_model');

            // 2. Filter for Salary related items AND Approved status
            foreach ($expenses as $exp) {
                $cat = strtolower($exp['category_title'] ?? '');
                $title = strtolower($exp['title'] ?? '');
                $desc = strtolower($exp['description'] ?? '');

                // Identify if it's a salary expense
                $is_salary = strpos($cat, 'salary') !== false || strpos($title, 'gaji') !== false || strpos($title, 'salary') !== false || strpos($desc, 'team member:') !== false;
                
                if (!$is_salary) continue;

                // Check all custom fields for "approved" status
                $cf_query = $this->db->table('custom_field_values')
                                     ->where('related_to_type', 'expenses')
                                     ->where('related_to_id', $exp['id'])
                                     ->get()->getResult();
                $is_approved = false;
                foreach ($cf_query as $cf) {
                    $v_lower = strtolower(trim((string)$cf->value));
                    if ($v_lower === 'approved' || $v_lower === 'approval') {
                        $is_approved = true;
                        break;
                    }
                }
                
                if (!$is_approved) {
                    continue;
                }

                // Check visibility based on role
                $can_view = false;
                if ($user->is_admin || $is_pm) {
                    $can_view = true;
                } else if ($is_hr_admin_marketing) {
                    // They can see expenses they created (user_id) or their own salary
                    if ($exp['user_id'] == $user_id || (isset($exp['created_by']) && $exp['created_by'] == $user_id)) {
                        $can_view = true;
                    }
                } else {
                    // Restricted roles: ONLY their own salary
                    if ($exp['user_id'] == $user_id) {
                        $can_view = true;
                    }
                }

                if ($can_view) {
                    $salaries[] = $exp;
                }
            }

            // Sort by date DESC
            usort($salaries, function ($a, $b) {
                return strcmp($b['expense_date'], $a['expense_date']);
            });

            // Pagination logic: 5 items per page
            $page = (int) $this->request->getGet('page');
            if ($page < 1) $page = 1;
            $limit = 5;
            $total_items = count($salaries);
            $total_pages = ceil($total_items / $limit);
            
            $offset = ($page - 1) * $limit;
            $paginated_data = array_slice($salaries, $offset, $limit);

            return $this->respond([
                "success" => true,
                "data" => $paginated_data,
                "meta" => [
                    "total_items" => $total_items,
                    "total_pages" => $total_pages,
                    "current_page" => $page,
                    "per_page" => $limit
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}