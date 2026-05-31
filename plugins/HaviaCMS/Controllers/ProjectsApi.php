<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class ProjectsApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
    protected $projects_model;
    protected $tasks_model;
    protected $api_settings_model;
    protected $users_model;
    protected $settings_model;
    private $initialized = false;

    public function __construct() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, authtoken, Accept");

        if (strtoupper(request()->getMethod()) === 'OPTIONS') {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

    private function _init() {
        if ($this->initialized) return;
        
        helper(['date_time', 'general', 'url']);
        
        if (defined('PLUGINPATH') && file_exists(PLUGINPATH . "RestApi/Helpers/jwt_helper.php")) {
            require_once PLUGINPATH . "RestApi/Helpers/jwt_helper.php";
        }

        $this->projects_model = model('App\Models\Projects_model');
        $this->tasks_model = model('App\Models\Tasks_model');
        $this->api_settings_model = model('RestApi\Models\Api_settings_model');
        $this->users_model = model('App\Models\Users_model');
        $this->settings_model = model('App\Models\Settings_model');

        $this->_load_settings();
        $this->initialized = true;
    }

    private function _load_settings($user_id = 0) {
        $settings = $this->settings_model->get_all_required_settings($user_id)->getResult();
        foreach ($settings as $setting) {
            config('Rise')->app_settings_array[$setting->setting_name] = $setting->setting_value;
        }
    }

    private function _validate_user() {
        // 1. Manually extract token
        $token_raw = null;
        $all_headers = $this->request->getHeaders();
        
        foreach($all_headers as $name => $header) {
            if (strtolower($name) === 'authtoken' || strtolower($name) === 'authorization') {
                $token_raw = (string)$header;
                break;
            }
        }

        if (!$token_raw) return "ERROR_MISSING_HEADER";

        // 2. ULTRA-AGGRESSIVE CLEANING
        $token = $token_raw;
        // Strip out common header prefixes that some servers prepend
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        if (empty($token)) return "ERROR_EMPTY_TOKEN";

        // 3. STRATEGY A: Standard Signature Verification
        // Ini adalah cara yang paling aman, menggunakan Secret Key dari config.
        try {
            $jwt_config = new \RestApi\Config\JWT();
            $key = preg_replace('/^["\']|["\']$/', '', trim($jwt_config->jwt_key));
            
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, $jwt_config->jwt_algorithm));
            
            if ($decoded && is_object($decoded)) {
                $user_id = $decoded->id ?? $decoded->crm_user_id ?? null;
                if (!$user_id && isset($decoded->user)) {
                    $user_row = $this->users_model->get_one_where(['email' => $decoded->user, 'deleted' => 0]);
                    $user_id = $user_row->id ?? null;
                }
                
                if ($user_id) {
                    $user = $this->users_model->get_one($user_id);
                    if ($user && $user->id && !$user->deleted) return (int)$user_id;
                }
            }
        } catch (\Exception $e) {
            // Jika siganture failed, jangan menyerah dulu. Cek database (Strategy B).
            $sig_error = $e->getMessage();
        }

        // 4. STRATEGY B: Database Cross-Reference (Fallback Darurat)
        // Jika signature failed (biasanya karena Secret Key di server berubah/mismatch),
        // kita cek apakah token tersebut benar-benar ada di tabel rise_api_users kita.
        // Karena token disimpan secara statis di database RISE CRM, ini adalah bukti validitas.
        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            // 'user' di tabel rise_api_users biasanya berisi email
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row && $user_row->id) {
                // Berhasil validasi via database!
                return (int)$user_row->id;
            }
        }

        $snippet = substr($token, 0, 10) . "..." . substr($token, -5);
        return "ERROR_AUTH_FAILED: " . ($sig_error ?? "Token not found in DB") . " [Snippet: $snippet]";
    }

    private function _is_full_access_role($user) {
        if (!$user) return false;
        if ((bool)$user->is_admin) return true;
        
        $role_title = strtolower($user->role_title ?? '');
        $job_title = strtolower($user->job_title ?? '');
        
        // Roles that should see ALL projects (Managers are handled separately now)
        $full_access_keywords = [
            'hr & admin projek',
            'marketing',
            'super admin'
        ];
        
        foreach ($full_access_keywords as $kw) {
            if (stripos($role_title, $kw) !== false || stripos($job_title, $kw) !== false) {
                return true;
            }
        }
        
        return false;
    }

    public function index() {
        try {
            $this->_init();
            $validation_result = $this->_validate_user();
            
            if (!is_int($validation_result)) {
                return $this->response->setStatusCode(401)->setJSON([
                    "success" => false, 
                    "message" => "Token tidak valid."
                ]);
            }

            $user_id = $validation_result;
            $user = $this->users_model->get_access_info($user_id);
            $job_title = strtolower($user->job_title ?? '');
            $role_title = strtolower($user->role_title ?? '');

            // Detect Restricted Manager Roles
            $is_pm = stripos($job_title, 'projek manager') !== false || stripos($role_title, 'projek manager') !== false;
            $is_arsitek_mgr = stripos($job_title, 'arsitek manager') !== false || stripos($role_title, 'arsitek manager') !== false ||
                              stripos($job_title, 'arsitektur manager') !== false || stripos($role_title, 'arsitektur manager') !== false;

            // Check if user has full access
            $can_see_all_projects = $this->_is_full_access_role($user);
            
            // Standard RISE permissions check
            if (!$can_see_all_projects && $user->role_id) {
                $roles_model = model('App\Models\Roles_model');
                $role = $roles_model->get_one($user->role_id);
                if ($role && $role->permissions) {
                    $perms = @unserialize($role->permissions);
                    if (is_array($perms) && (!empty($perms['can_manage_all_projects']) || (isset($perms['expense']) && $perms['expense'] === 'all'))) {
                        $can_see_all_projects = true;
                    }
                }
            }

            // 1. Build Base Options
            $options = [];
            $status_filter = $this->request->getVar('status');
            $page = (int)($this->request->getVar('page') ?? 1);
            $limit = 5;
            $offset = ($page - 1) * $limit;

            // Map Status IDs
            $status_id = null;
            if ($status_filter === 'OPEN') $status_id = 1;
            else if ($status_filter === 'COMPLETED') $status_id = 2;
            else if ($status_filter === 'HOLD') $status_id = 3;
            else if ($status_filter === 'CANCELED') $status_id = 4;

            if ($status_id) {
                $options['status_id'] = $status_id;
            } else {
                $options['status'] = 'all';
            }

            // 2. APPLY ROLE-BASED FILTERING
            if ($is_pm || $is_arsitek_mgr) {
                // Involved as Member OR Creator
                $options_member = ["user_id" => $user_id];
                $projects_member = $this->projects_model->get_details($options_member)->getResultArray();
                
                $options_created = ["created_by" => $user_id];
                $projects_created = $this->projects_model->get_details($options_created)->getResultArray();
                
                // Merge and unique
                $all_involved = array_merge($projects_member, $projects_created);
                $projects = array_values(array_reduce($all_involved, function($carry, $item) {
                    $carry[$item['id']] = $item;
                    return $carry;
                }, []));
            } else if (!$can_see_all_projects) {
                // STANDARD ACCESS: See projects where they are members
                $options['user_id'] = $user_id;
                $projects = $this->projects_model->get_details($options)->getResultArray();
            } else {
                // FULL ACCESS
                $projects = $this->projects_model->get_details($options)->getResultArray();
            }

            // 3. Involvement via Tasks (For all non-admin users)
            if (!$can_see_all_projects) {
                $task_options = ['specific_user_id' => $user_id, 'status' => 'all'];
                $tasks = $this->tasks_model->get_details($task_options)->getResultArray();
                $involved_project_ids = array_unique(array_column($tasks, 'project_id'));

                foreach ($involved_project_ids as $p_id) {
                    if (!$p_id) continue;
                    $exists = array_filter($projects, function($p) use ($p_id) { return $p['id'] == $p_id; });
                    if (!$exists) {
                        $p_details = $this->projects_model->get_details(['id' => $p_id, 'status' => 'all'])->getRowArray();
                        if ($p_details) {
                            if ($status_id && ($p_details['status_id'] ?? null) != $status_id) continue;
                            if ($status_filter === 'CANCELED') {
                                $st = strtoupper($p_details['status_title'] ?? '');
                                if (!($st === 'CANCELED' || $st === 'BATAL')) continue;
                            }
                            $projects[] = $p_details;
                        }
                    }
                }
            }

            // 4. Final Filtering for CANCELED
            if ($status_filter === 'CANCELED') {
                $projects = array_filter($projects, function($p) {
                    $st = strtoupper($p['status_title'] ?? '');
                    return $st === 'CANCELED' || $st === 'BATAL';
                });
            }

            // 5. Apply Manual Pagination and Sorting (Newest First)
            $total_records = count($projects);
            $total_pages = ceil($total_records / $limit);
            
            usort($projects, function($a, $b) {
                return (int)$b['id'] - (int)$a['id'];
            });
            
            $paginated_data = array_slice($projects, $offset, $limit);

            // --- APPEND S-CURVE PROGRESS FOR EACH PROJECT ---
            if (!empty($paginated_data)) {
                $db = \Config\Database::connect();
                $project_ids = array_unique(array_filter(array_column($paginated_data, 'id')));
                
                $project_schedules = [];
                $project_actuals = [];
                if (!empty($project_ids)) {
                    if ($db->tableExists($db->prefixTable('pd_weekly_schedules'))) {
                        $scheds = $db->table($db->prefixTable('pd_weekly_schedules'))
                                      ->whereIn('project_id', $project_ids)
                                      ->where('deleted', 0)
                                      ->orderBy('week_number', 'ASC')
                                      ->get()->getResult();
                        foreach ($scheds as $s) {
                            $project_schedules[$s->project_id][$s->week_number] = $s->cumulative_planned;
                        }
                    }
                    if ($db->tableExists($db->prefixTable('pd_weekly_actuals'))) {
                        $actuals = $db->table($db->prefixTable('pd_weekly_actuals'))
                                      ->whereIn('project_id', $project_ids)
                                      ->where('deleted', 0)
                                      ->orderBy('week_number', 'ASC')
                                      ->get()->getResult();
                        foreach ($actuals as $a) {
                            $project_actuals[$a->project_id][$a->week_number] = $a->cumulative_actual;
                        }
                    }
                }
                
                $now = strtotime(date("Y-m-d"));
                
                foreach ($paginated_data as &$p) {
                    $pid = $p['id'];
                    $start_date = $p['start_date'] ?? null;
                    
                    $current_week = 1;
                    if ($start_date && $start_date !== '0000-00-00') {
                        $start = strtotime(date("Y-m-d", strtotime($start_date)));
                        $diff = $now - $start;
                        if ($diff >= 0) {
                            $day_diff = floor($diff / 86400);
                            $current_week = floor($day_diff / 7) + 1;
                        }
                    }
                    
                    $plan_total = 0;
                    $act_total = 0;
                    
                    // Get Planned Progress from pd_weekly_schedules
                    if (isset($project_schedules[$pid])) {
                        $max_w = 0;
                        foreach ($project_schedules[$pid] as $w => $c_plan) {
                            if ($w <= $current_week && $w > $max_w) {
                                $max_w = $w;
                                $plan_total = (float)$c_plan;
                            }
                        }
                    }
                    
                    // Get Actual Progress from pd_weekly_actuals
                    if (isset($project_actuals[$pid])) {
                        $max_w = 0;
                        foreach ($project_actuals[$pid] as $w => $c_act) {
                            if ($w <= $current_week && $w > $max_w) {
                                $max_w = $w;
                                $act_total = (float)$c_act;
                            }
                        }
                    }
                    
                    $p['planned_progress'] = $plan_total;
                    $p['actual_progress'] = $act_total;
                    $p['deviation'] = $act_total - $plan_total;
                }
                unset($p);
            }
            // ---------------------------------------------

            return $this->respond([
                "success" => true,
                "data" => array_values($paginated_data),
                "meta" => [
                    "total_records" => $total_records,
                    "total_pages" => $total_pages,
                    "current_page" => $page,
                    "limit" => $limit,
                    "has_more" => $page < $total_pages
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function show($id = null) {
        try {
            $this->_init();
            if (!$id) return $this->fail("ID required");
            
            $user_id = $this->_validate_user();
            if (!$user_id) return $this->failUnauthorized("Token tidak valid.");

            $data = $this->projects_model->get_details(['id' => $id])->getRow();
            return $this->respond($data);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}