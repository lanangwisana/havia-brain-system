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
        /** @var \CodeIgniter\HTTP\IncomingRequest $request */
        $request = $this->request;
        $all_headers = $request->getHeaders();

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

            // 1. Precise Role Detection
            $is_admin = (int)$user->is_admin === 1;
            $job_title = trim(strtolower($user->job_title ?? ''));
            
            $roles_model = model('App\Models\Roles_model');
            $role_title = '';
            if ($user->role_id) {
                $role = $roles_model->get_one($user->role_id);
                $role_title = trim(strtolower($role->title ?? ''));
            }

            // Define Role Flags
            $is_hr_admin = ($role_title === 'hr & admin projek' || $job_title === 'hr & admin projek');
            $is_pm = ($role_title === 'projek manager' || $job_title === 'projek manager' || $job_title === 'pm');
            $is_arsitek_mgr = ($role_title === 'arsitek manager' || $job_title === 'arsitek manager');
            $is_marketing = ($role_title === 'marketing' || $job_title === 'marketing');
            
            // Simplified Full Access (Admin and HR & Admin Projek)
            $has_full_access = $is_admin || $is_hr_admin || $role_title === 'admin' || $job_title === 'admin';

            // Restricted check (excluding Arsitek Manager and Marketing now)
            $is_restricted = (stripos($job_title, 'arsitek') !== false && stripos($job_title, 'manager') === false) || 
                             (stripos($role_title, 'arsitek') !== false && stripos($role_title, 'manager') === false) ||
                             stripos($job_title, 'drafter') !== false || stripos($role_title, 'drafter') !== false || 
                             stripos($job_title, 'estimator') !== false || stripos($role_title, 'estimator') !== false || 
                             stripos($job_title, 'ob') !== false || stripos($role_title, 'ob') !== false || 
                             stripos($job_title, 'office boy') !== false || stripos($role_title, 'office boy') !== false;

            if ($is_restricted && !$has_full_access && !$is_marketing) {
                return $this->respond([
                    "success" => true,
                    "totals" => [
                        "total_budget" => 0,
                        "total_balance" => 0
                    ],
                    "data" => [],
                    "meta" => [
                        "total_items" => 0,
                        "total_pages" => 0,
                        "current_page" => 1,
                        "per_page" => 5
                    ]
                ]);
            }

            // 2. Fetch Projects
            if ($has_full_access) {
                // Full Access sees everything
                $projects = $this->db->table('projects')
                    ->select('projects.*, project_status.title AS status_title')
                    ->join('project_status', 'project_status.id = projects.status_id', 'left')
                    ->where('projects.deleted', 0)
                    ->orderBy('projects.created_date', 'DESC')
                    ->get()->getResultArray();
            } else if ($is_pm || $is_arsitek_mgr || $is_marketing) {
                // PM, Arsitek Manager, & Marketing see projects they are members of OR created
                $options = array("user_id" => $user_id);
                $projects_member = $this->projects_model->get_details($options)->getResultArray();
                
                $options_created = array("created_by" => $user_id);
                $projects_created = $this->projects_model->get_details($options_created)->getResultArray();
                
                // Merge and unique by ID
                $all_involved = array_merge($projects_member, $projects_created);
                $projects = array_values(array_reduce($all_involved, function($carry, $item) {
                    $carry[$item['id']] = $item;
                    return $carry;
                }, []));
            } else {
                // Standard filtering: Team members or client
                $options = array();
                if ($user->user_type === "client") {
                    $options["client_id"] = $user->client_id;
                } else {
                    $options["user_id"] = $user_id;
                }
                $projects = $this->projects_model->get_details($options)->getResultArray();
            }

            // Optimization: Load all expenses for involved projects
            $project_ids = array_column($projects, 'id');
            if (empty($project_ids)) $project_ids = [0];
            
            $all_expenses = $this->db->table('expenses')
                ->whereIn('project_id', $project_ids)
                ->where('deleted', 0)
                ->get()->getResultArray();

            // Load schedules and actuals for progress calculation
            $project_schedules = [];
            $project_actuals = [];
            if ($this->db->tableExists($this->db->prefixTable('pd_weekly_schedules'))) {
                $scheds = $this->db->table($this->db->prefixTable('pd_weekly_schedules'))
                                ->whereIn('project_id', $project_ids)
                                ->where('deleted', 0)
                                ->orderBy('week_number', 'ASC')
                                ->get()->getResult();
                foreach ($scheds as $s) {
                    $project_schedules[$s->project_id][$s->week_number] = $s->cumulative_planned;
                }
            }
            if ($this->db->tableExists($this->db->prefixTable('pd_weekly_actuals'))) {
                $actuals = $this->db->table($this->db->prefixTable('pd_weekly_actuals'))
                                ->whereIn('project_id', $project_ids)
                                ->where('deleted', 0)
                                ->orderBy('week_number', 'ASC')
                                ->get()->getResult();
                foreach ($actuals as $a) {
                    $project_actuals[$a->project_id][$a->week_number] = $a->cumulative_actual;
                }
            }

            $overall_total_budget = 0;
            $overall_total_balance = 0;
            $summary_data = [];
            /** @var \CodeIgniter\HTTP\IncomingRequest $req */
            $req = $this->request;
            $search = strtolower(trim((string)$req->getGet('search')));

            foreach ($projects as $project) {
                $status_title = strtolower(trim($project['status_title'] ?? ''));
                if ($status_title === 'completed' || $status_title === 'canceled') continue;

                if ($search) {
                    $title_lower = strtolower($project['title'] ?? '');
                    if (strpos($title_lower, $search) === false) {
                        continue;
                    }
                }

                $project_id = $project['id'];

                // Budget Calculation
                $raw_price = (string) ($project['price'] ?? '0');
                $clean_price = preg_replace('/[^\d,]/', '', $raw_price);
                $project_price = (strpos($clean_price, ',') !== false) ? (float) explode(',', $clean_price)[0] : (float) $clean_price;

                // Expense Calculation
                $project_expenses = array_filter($all_expenses, function($e) use ($project_id) {
                    return $e['project_id'] == $project_id;
                });
                
                $total_expense = 0;
                $expense_count = 0;
                $user_has_contribution = false;

                foreach ($project_expenses as $exp) {
                    // Check category: Project Expense
                    $cat_title = strtolower($exp['category_title'] ?? '');
                    if (strpos($cat_title, 'project expense') === false && $exp['category_id'] != 2) continue;

                    // Approval Check
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

                        if ($exp['user_id'] == $user_id || (isset($exp['created_by']) && $exp['created_by'] == $user_id)) {
                            $user_has_contribution = true;
                        }
                    }
                }

                // Visibility logic
                $show_project = false;
                if ($has_full_access || $user->user_type === "client") {
                    $show_project = true;
                } else if ($is_pm || $is_arsitek_mgr) {
                    // PM & Arsitek Manager see projects where they are creators or members (already filtered in fetch)
                    $show_project = true;
                } else {
                    // Others: standard team member check
                    $show_project = true; 
                }

                if ($show_project) {
                    $balance = $project_price - $total_expense;
                    $progress = 0;
                    if (isset($project['total_points']) && $project['total_points'] > 0) {
                        $progress = round(($project['completed_points'] / $project['total_points']) * 100);
                    } else if (isset($project['total_tasks']) && $project['total_tasks'] > 0) {
                        $progress = round(($project['completed_tasks'] / $project['total_tasks']) * 100);
                    }

                    $overall_total_budget += $project_price;
                    $overall_total_balance += $balance;

                    $expense_ratio = ($project_price > 0) ? round(($total_expense / $project_price) * 100) : 0;

                    // Calculate PLN, ACT, DEV
                    $plan_total = 0;
                    $act_total = 0;
                    $expected_budget = 0;
                    $budget_status = 'on';
                    
                    if (isset($project['start_date']) && $project['start_date'] && $project['start_date'] !== '0000-00-00') {
                        $start = strtotime(date("Y-m-d", strtotime($project['start_date'])));
                        $now = strtotime(date("Y-m-d"));
                        $diff = $now - $start;
                        
                        $current_week = 1;
                        if ($diff >= 0) {
                            $day_diff = floor($diff / 86400);
                            $current_week = floor($day_diff / 7) + 1;
                        }

                        if (isset($project_schedules[$project_id])) {
                            $max_w = 0;
                            foreach ($project_schedules[$project_id] as $w => $c_plan) {
                                if ($w <= $current_week && $w > $max_w) {
                                    $max_w = $w;
                                    $plan_total = (float)$c_plan;
                                }
                            }
                        }
                        if (isset($project_actuals[$project_id])) {
                            $max_w = 0;
                            foreach ($project_actuals[$project_id] as $w => $c_act) {
                                if ($w <= $current_week && $w > $max_w) {
                                    $max_w = $w;
                                    $act_total = (float)$c_act;
                                }
                            }
                        }

                        // Calculate Expected Budget (Total RAB of tasks up to this week)
                        $week_end_timestamp = $start + ($current_week * 7 * 86400) - 86400;
                        $week_end_date = date("Y-m-d", $week_end_timestamp);
                        
                        $all_tasks = $this->db->table('tasks')
                            ->select('id, parent_task_id, start_date')
                            ->where('project_id', $project_id)
                            ->where('deleted', 0)
                            ->get()->getResult();
                            
                        $parent_ids = [];
                        foreach ($all_tasks as $t) {
                            if ($t->parent_task_id) {
                                $parent_ids[$t->parent_task_id] = true;
                            }
                        }
                        
                        $task_weights = [];
                        if ($this->db->tableExists($this->db->prefixTable('pd_project_weights'))) {
                            $weights = $this->db->table($this->db->prefixTable('pd_project_weights'))
                                ->where('project_id', $project_id)
                                ->where('deleted', 0)
                                ->get()->getResult();
                            foreach ($weights as $w) {
                                $task_weights[$w->task_id] = (float) $w->nominal_rab;
                            }
                        }
                        
                        foreach ($all_tasks as $t) {
                            $is_leaf = !isset($parent_ids[$t->id]);
                            if ($is_leaf) {
                                $t_start = $t->start_date;
                                if ($t_start && $t_start !== '0000-00-00' && $t_start <= $week_end_date) {
                                    $expected_budget += ($task_weights[$t->id] ?? 0);
                                }
                            }
                        }
                        
                        // Compare Expected vs Actual
                        if ($expected_budget > $total_expense) {
                            $budget_status = 'under';
                        } else if ($expected_budget < $total_expense) {
                            $budget_status = 'over';
                        }
                    } else {
                        // Fallback
                        $plan_total = $progress;
                        $act_total = $progress;
                    }
                    
                    $deviation = $act_total - $plan_total;

                    $summary_data[] = [
                        'project_id' => $project_id,
                        'project_title' => $project['title'],
                        'project_price' => $project_price,
                        'total_expense' => $total_expense,
                        'balance' => $balance,
                        'progress' => $progress,
                        'planned_progress' => $plan_total,
                        'actual_progress' => $act_total,
                        'deviation' => $deviation,
                        'expense_ratio' => $expense_ratio,
                        'expected_budget' => $expected_budget,
                        'budget_status' => $budget_status,
                        'status_title' => $project['status_title'] ?? 'Open',
                        'expense_count' => $expense_count
                    ];
                }
            }

            // Sorting and Pagination
            usort($summary_data, function ($a, $b) {
                return (int) $b['project_id'] - (int) $a['project_id'];
            });

            /** @var \CodeIgniter\HTTP\IncomingRequest $req */
            $req = $this->request;
            $page = (int) $req->getGet('page');
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
            if (!is_int($validation)) return $this->failUnauthorized($validation);

            $user_id = $validation;
            $user = $this->users_model->get_one($user_id);
            $job_title = trim(strtolower($user->job_title ?? ''));
            
            $roles_model = model('App\Models\Roles_model');
            $role_title = '';
            if ($user->role_id) {
                $role = $roles_model->get_one($user->role_id);
                $role_title = trim(strtolower($role->title ?? ''));
            }

            // Role Flags
            $is_admin = (int)$user->is_admin === 1;
            $is_hr_admin = ($role_title === 'hr & admin projek' || $job_title === 'hr & admin projek');
            
            $has_full_access = $is_admin || $is_hr_admin || $role_title === 'admin' || $job_title === 'admin';

            $expenses = $this->expenses_model->get_details([])->getResultArray();
            $salaries = [];

            foreach ($expenses as $exp) {
                $cat = strtolower($exp['category_title'] ?? '');
                $title = strtolower($exp['title'] ?? '');
                $desc = strtolower($exp['description'] ?? '');

                // Identify Salary
                $is_salary = strpos($cat, 'salary') !== false || strpos($title, 'gaji') !== false || strpos($title, 'salary') !== false || strpos($desc, 'team member:') !== false;
                if (!$is_salary) continue;

                // Approval Check
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
                if (!$is_approved) continue;

                // Visibility
                $can_view = false;
                if ($has_full_access) {
                    $can_view = true;
                } else {
                    // PM, Arsitek Manager, and others: ONLY own salary
                    if ($exp['user_id'] == $user_id) $can_view = true;
                }

                if ($can_view) $salaries[] = $exp;
            }

            usort($salaries, function ($a, $b) {
                return strcmp($b['expense_date'], $a['expense_date']);
            });

            /** @var \CodeIgniter\HTTP\IncomingRequest $req */
            $req = $this->request;
            $page = (int) $req->getGet('page');
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