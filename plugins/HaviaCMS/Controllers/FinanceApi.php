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
            if (!$can_see_all_projects && $user->role_id) {
                $roles_model = model('App\Models\Roles_model');
                $role = $roles_model->get_one($user->role_id);
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

            // 1. Get projects.
            $options = array();
            if ($user->user_type === "client") {
                $options["client_id"] = $user->client_id;
            } else if (!$can_see_all_projects) {
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

                // 2. Get Expenses specifically for this project (Category ID 2: Project Expense)
                $expenses = $this->expenses_model->get_details(['project_id' => $project_id, 'category_id' => 2])->getResultArray();
                
                // Jika tidak memiliki pengeluaran di kategori tersebut, lewati (proyek tidak dimasukkan)
                if (empty($expenses)) {
                    continue;
                }

                $total_expense = 0;
                $expense_count = 0;
                $expense_titles = [];
                $custom_field_values_model = model('App\Models\Custom_field_values_model');

                foreach ($expenses as $exp) {
                    $cf_val = $custom_field_values_model->get_one_where([
                        'related_to_type' => 'expenses',
                        'related_to_id' => $exp['id'],
                        'custom_field_id' => 3
                    ]);

                    // Tambahkan ke total HANYA JIKA Approval Status adalah "Approval" atau "Approved"
                    $v_lower = $cf_val && isset($cf_val->value) ? strtolower(trim((string)$cf_val->value)) : '';
                    if ($v_lower === 'approved' || $v_lower === 'approval') {
                        $amt = (float) $exp['amount'];
                        $tax_percentage = (float) ($exp['tax_percentage'] ?? 0);
                        $tax_percentage2 = (float) ($exp['tax_percentage2'] ?? 0);

                        $tax = ($tax_percentage / 100) * $amt;
                        $tax2 = ($tax_percentage2 / 100) * $amt;

                        $total_expense += ($amt + $tax + $tax2);
                        $expense_count++;
                        $expense_titles[] = $exp['title'];
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
                if ($can_see_all_projects || $user->user_type === "client") {
                    $is_pic = true;
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

            // Limit to 5 items unless 'full' is requested
            // DIHAPUS: User ingin melihat semua data di mobile
            /*
            $full = $this->request->getGet('full');
            if (!$full) {
                $summary_data = array_slice($summary_data, 0, 5);
            }
            */

            return $this->respond([
                "success" => true,
                "data" => $summary_data
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

            // 1. Define options. If admin, get all expenses. If staff, get only theirs.
            $options = [];
            if (!$user->is_admin) {
                $options['user_id'] = $user_id;
            }

            $expenses = $this->expenses_model->get_details($options)->getResultArray();
            $salaries = [];

            // 2. Filter for Salary related items
            // IF ADMIN: Filter strictly for salary keywords
            // IF STAFF: Show all expenses assigned to them (trusted as their salary/payroll)
            if ($user->is_admin) {
                $salaries = array_filter($expenses, function ($exp) {
                    $cat = strtolower($exp['category_title'] ?? '');
                    $title = strtolower($exp['title'] ?? '');
                    // Also check description for 'salary' or 'gaji' just in case
                    $desc = strtolower($exp['description'] ?? '');

                    return strpos($cat, 'salary') !== false ||
                        strpos($title, 'gaji') !== false ||
                        strpos($title, 'salary') !== false ||
                        strpos($desc, 'team member:') !== false; // Rise CRM default salary format
                });
                $salaries = array_values($salaries);
            } else {
                $salaries = $expenses;
            }

            // Sort by date DESC
            usort($salaries, function ($a, $b) {
                return strcmp($b['expense_date'], $a['expense_date']);
            });

            // Limit to 5 items unless 'full' is requested
            $full = $this->request->getGet('full');
            if (!$full) {
                $salaries = array_slice($salaries, 0, 5);
            }

            return $this->respond([
                "success" => true,
                "data" => $salaries
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}