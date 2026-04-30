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

            // Explicit Role checks based on job_title or role_title
            $is_pm = $can_see_all_projects || strpos($job_title, 'project manager') !== false || strpos($role_title, 'project manager') !== false;
            $is_hr_admin_marketing = strpos($job_title, 'hr') !== false || strpos($role_title, 'hr') !== false || strpos($job_title, 'admin projek') !== false || strpos($role_title, 'admin projek') !== false || strpos($job_title, 'admin project') !== false || strpos($role_title, 'admin project') !== false || strpos($job_title, 'marketing') !== false || strpos($role_title, 'marketing') !== false;
            $is_restricted = strpos($job_title, 'arsitektur') !== false || strpos($role_title, 'arsitektur') !== false || strpos($job_title, 'drafter') !== false || strpos($role_title, 'drafter') !== false || strpos($job_title, 'estimator') !== false || strpos($role_title, 'estimator') !== false || strpos($job_title, 'ob') !== false || strpos($role_title, 'ob') !== false || strpos($job_title, 'office boy') !== false || strpos($role_title, 'office boy') !== false;

            if ($is_restricted && !$user->is_admin) {
                return $this->respond(["success" => true, "data" => []]);
            }

            // 1. Get projects.
            $options = array();
            if ($user->user_type === "client") {
                $options["client_id"] = $user->client_id;
            } else if (!$is_pm && !$user->is_admin && !$is_hr_admin_marketing) {
                // Non-admin/non-PM staff: only projects they are members of
                $options["user_id"] = $user_id;
            }

            $projects = $this->projects_model->get_details($options)->getResultArray();

            // 1.5 Deep Discovery: Tarik project dimana user tidak masuk project_members 
            // tapi ditugaskan di dalam Task-nya.
            if (!$can_see_all_projects && $user->user_type !== "client") {
                $tasks_model = model('App\Models\Tasks_model');
                $my_tasks = $tasks_model->get_details(['specific_user_id' => $user_id, 'status' => 'all'])->getResultArray();

                $discovered_pids = [];
                foreach ($my_tasks as $t) {
                    if ($t['project_id'])
                        $discovered_pids[] = $t['project_id'];
                }
                $discovered_pids = array_unique($discovered_pids);

                foreach ($discovered_pids as $pid) {
                    $exists = false;
                    foreach ($projects as $p) {
                        if ($p['id'] == $pid) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $p_details = $this->projects_model->get_details(['id' => $pid, 'status' => 'all'])->getRowArray();
                        if ($p_details)
                            $projects[] = $p_details;
                    }
                }
            }

            $summary_data = [];
            foreach ($projects as $project) {
                $project_id = $project['id'];

                // 2. Get Expenses specifically for this project
                $expenses = $this->expenses_model->get_details(['project_id' => $project_id])->getResultArray();
                
                // Jika tidak memiliki pengeluaran di kategori tersebut, lewati (proyek tidak dimasukkan)
                if (empty($expenses)) {
                    continue;
                }

                $total_expense = 0;
                $expense_count = 0;
                $expense_titles = [];
                $custom_field_values_model = model('App\Models\Custom_field_values_model');
                $user_created_approved_expense = false;

                foreach ($expenses as $exp) {
                    $cat_title = strtolower($exp['category_title'] ?? '');
                    if (strpos($cat_title, 'project expense') === false && $exp['category_id'] != 2) {
                        continue;
                    }

                    // Check all custom fields for this expense for "approved" or "approval"
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

                    // Tambahkan ke total HANYA JIKA Approval Status adalah "Approval" atau "Approved"
                    if ($is_approved) {
                        $amt = (float) $exp['amount'];
                        $tax_percentage = (float) ($exp['tax_percentage'] ?? 0);
                        $tax_percentage2 = (float) ($exp['tax_percentage2'] ?? 0);

                        $tax = ($tax_percentage / 100) * $amt;
                        $tax2 = ($tax_percentage2 / 100) * $amt;

                        $total_expense += ($amt + $tax + $tax2);
                        $expense_count++;
                        $expense_titles[] = $exp['title'];

                        if ($exp['user_id'] == $user_id || (isset($exp['created_by']) && $exp['created_by'] == $user_id)) {
                            $user_created_approved_expense = true;
                        }
                    }
                }

                // 3. Get Project Progress (Tasks)
                $progress = 0;
                if (isset($project['total_points']) && $project['total_points'] > 0) {
                    $progress = round(($project['completed_points'] / $project['total_points']) * 100);
                } else if (isset($project['total_tasks']) && $project['total_tasks'] > 0) {
                    $progress = round(($project['completed_tasks'] / $project['total_tasks']) * 100);
                }

                $project_price = (float) ($project['price'] ?? 0);
                $balance = $project_price - $total_expense;

                // 4. Check if current user is Admin/PM atau PIC secara eksplisit
                $is_pic = false;
                if ($user->is_admin || $is_pm || $user->user_type === "client") {
                    $is_pic = true;
                } else if ($is_hr_admin_marketing) {
                    if ($user_created_approved_expense) {
                        $is_pic = true;
                    }
                } else {
                    // Syarat 1: Cek native RISE, apakah dia Leader di project members?
                    $member_row = $this->db->table('project_members')
                        ->where(['user_id' => $user_id, 'project_id' => $project_id, 'is_leader' => 1, 'deleted' => 0])
                        ->get()
                        ->getRow();

                    if ($member_row) {
                        $is_pic = true;
                    } else {
                        // Syarat 2: Cek apakah user adalah 'assigned_to' (PIC) di minimal 1 TASK di project ini.
                        // Kolaborator (yang ada di kolom collaborators) tidak akan menangkap ini jika kita pakai spesifik 'assigned_to'.
                        $tasks_model = model('App\Models\Tasks_model');
                        $pic_task = $tasks_model->get_details([
                            'project_id' => $project_id,
                            'assigned_to' => $user_id,
                            'status' => 'all'
                        ])->getRow();

                        if ($pic_task) {
                            $is_pic = true;
                        }
                    }
                }

                // RBAC Check: Only include in summary if user is Admin OR PIC
                // Dan hanya jika ada pengeluaran yang sudah APPROVED
                if ($is_pic && $total_expense > 0) {
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

            // Sort by project_id DESC to show newest projects first
            usort($summary_data, function ($a, $b) {
                return (int) $b['project_id'] - (int) $a['project_id'];
            });

            // Pagination logic: 5 items per page
            $page = (int) $this->request->getGet('page');
            if ($page < 1) $page = 1;
            $limit = 5;
            $total_items = count($summary_data);
            $total_pages = ceil($total_items / $limit);
            
            $offset = ($page - 1) * $limit;
            $paginated_data = array_slice($summary_data, $offset, $limit);

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