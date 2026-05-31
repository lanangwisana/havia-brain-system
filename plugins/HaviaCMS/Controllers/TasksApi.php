<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class TasksApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
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
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        if (empty($token)) return "ERROR_EMPTY_TOKEN";

        // 3. STRATEGY A: Standard Signature Verification
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
            $sig_error = $e->getMessage();
        }

        // 4. STRATEGY B: Database Cross-Reference (Fallback)
        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row && $user_row->id) {
                return (int)$user_row->id;
            }
        }

        $snippet = substr($token, 0, 10) . "..." . substr($token, -5);
        return "ERROR_AUTH_FAILED: " . ($sig_error ?? "Token not found in DB") . " [Snippet: $snippet]";
    }

    private function _is_full_access_role($user) {
        if (!$user) return false;
        if ((bool)$user->is_admin) return true;
        
        $role_title = $user->role_title ?? '';
        $job_title = $user->job_title ?? '';
        
        $full_access_roles = [
            'Arsitektur Manager',
            'HR & Admin Projek',
            'Marketing',
            'Projek Manager',
            'Super Admin'
        ];
        
        foreach ($full_access_roles as $role) {
            if (stripos($role_title, $role) !== false || stripos($job_title, $role) !== false) {
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
                    "message" => "Token tidak valid (Tasks)."
                ]);
            }

            $user_id = $validation_result;
            $user = $this->users_model->get_access_info($user_id);

            // Access detection
            $can_see_all_projects = $this->_is_full_access_role($user);

            $project_id = $this->request->getGet('project_id');
            $status_filter = strtoupper($this->request->getGet('status') ?? 'ALL');
            $page = (int)($this->request->getGet('page') ?? 1);
            $limit = 5; 
            
            $options = [];
            if ($project_id) {
                $options['project_id'] = $project_id;
                
                // Lapisan keamanan: pastikan non-admin/non-privileged benaran member
                if (!$can_see_all_projects) {
                    $projects_model = model('App\Models\Projects_model');
                    $p_check = $projects_model->get_details(['id' => $project_id, 'user_id' => $user_id])->getRow();
                    
                    if (!$p_check) {
                        $t_check = $this->tasks_model->get_details(['project_id' => $project_id, 'specific_user_id' => $user_id])->getRow();
                        if (!$t_check) {
                            return $this->response->setStatusCode(403)->setJSON(["success" => false, "message" => "Anda tidak berhak mengakses tugas untuk project ini."]);
                        }
                    }
                }
            } else {
                $options['specific_user_id'] = $user_id;
            }
            
            // Note: RISE tasks get_details often defaults to open tasks only if no status is provided
            if ($status_filter === 'ALL' || $status_filter === 'OVERDUE' || $status_filter === '7_DAYS') {
                $options['status'] = 'all'; 
            } else if ($status_filter === 'DONE') {
                $options['status_id'] = 3; // Standard Rise: 3 = Done
            } else if ($status_filter === 'IN PROGRESS') {
                $options['status_id'] = 2; // Standard Rise: 2 = In Progress
            } else if ($status_filter === 'TO DO') {
                $options['status_id'] = 1; // Standard Rise: 1 = To Do
            }

            $all_tasks = $this->tasks_model->get_details($options)->getResultArray();

            // Manual strict filtering in PHP to guarantee correct results
            if ($status_filter !== 'ALL') {
                $today_date = date('Y-m-d');
                $future_7_date = date('Y-m-d', strtotime('+7 days'));

                $all_tasks = array_filter($all_tasks, function($t) use ($status_filter, $today_date, $future_7_date) {
                    $st = strtoupper($t['status_title'] ?? $t['status'] ?? '');
                    $sid = (int)($t['status_id'] ?? 0);
                    $is_done = ($st === 'DONE' || $st === 'COMPLETED' || $st === 'SELESAI' || $sid === 3);
                    
                    if ($status_filter === 'OVERDUE') {
                        if ($is_done || empty($t['deadline']) || $t['deadline'] === '0000-00-00') return false;
                        return $t['deadline'] < $today_date;
                    }
                    if ($status_filter === '7_DAYS') {
                        if ($is_done || empty($t['deadline']) || $t['deadline'] === '0000-00-00') return false;
                        return $t['deadline'] >= $today_date && $t['deadline'] <= $future_7_date;
                    }

                    if ($status_filter === 'DONE') {
                        return $is_done;
                    }
                    if ($status_filter === 'IN PROGRESS') {
                        return ($st === 'IN PROGRESS' || $st === 'ACTIVE' || $st === 'SEDANG DIKERJAKAN' || $sid === 2);
                    }
                    if ($status_filter === 'TO DO') {
                        return ($st === 'TO DO' || $st === 'OPEN' || $st === 'BARU' || $sid === 1);
                    }
                    return $st === $status_filter;
                });
                $all_tasks = array_values($all_tasks); // Re-index for consistent pagination
            }

            // Custom sorting: Priority (To Do > In Progress > Done)
            usort($all_tasks, function($a, $b) use ($status_filter) {
                if ($status_filter === 'OVERDUE' || $status_filter === '7_DAYS') {
                    $dA = empty($a['deadline']) ? 0 : strtotime($a['deadline']);
                    $dB = empty($b['deadline']) ? 0 : strtotime($b['deadline']);
                    // Nearest deadline (smallest timestamp) comes first
                    if ($dA === $dB) {
                        return strtotime($b['start_date'] ?? '') - strtotime($a['start_date'] ?? '');
                    }
                    return $dA - $dB;
                }

                if ($status_filter === 'ALL') {
                    // Sort by oldest first (ID ascending)
                    return (int)($a['id'] ?? 0) - (int)($b['id'] ?? 0);
                }

                $priority = [
                    'TO DO' => 1,
                    'OPEN' => 1,
                    'IN PROGRESS' => 2,
                    'ACTIVE' => 2,
                    'DONE' => 3,
                    'COMPLETED' => 3,
                    'CLOSED' => 3
                ];
                
                $stA = strtoupper($a['status_title'] ?? $a['status'] ?? 'TO DO');
                $stB = strtoupper($b['status_title'] ?? $b['status'] ?? 'TO DO');
                
                $pA = $priority[$stA] ?? 1;
                $pB = $priority[$stB] ?? 1;
                
                if ($pA !== $pB) return $pA - $pB;
                return strtotime($b['start_date'] ?? '') - strtotime($a['start_date'] ?? '');
            });

            // Manual Pagination
            $total_records = count($all_tasks);
            $total_pages = ceil($total_records / $limit);
            $offset = ($page - 1) * $limit;
            $paged_data = array_slice($all_tasks, $offset, $limit);

            // --- APPEND S-CURVE PROGRESS FOR EACH TASK ---
            if (!empty($paged_data)) {
                $db = \Config\Database::connect();
                $project_ids = array_unique(array_filter(array_column($paged_data, 'project_id')));
                $task_ids = array_unique(array_filter(array_column($paged_data, 'id')));
                
                $project_start_dates = [];
                if (!empty($project_ids)) {
                    $projects = $db->table($db->prefixTable('projects'))
                                   ->select('id, start_date')
                                   ->whereIn('id', $project_ids)
                                   ->get()->getResult();
                    foreach ($projects as $p) {
                        $project_start_dates[$p->id] = $p->start_date;
                    }
                }

                $task_weights = [];
                if (!empty($task_ids)) {
                    // Cek apakah tabel pd_project_weights ada (agar aman)
                    if ($db->tableExists($db->prefixTable('pd_project_weights'))) {
                        $weights = $db->table($db->prefixTable('pd_project_weights'))
                                      ->select('task_id, weekly_weights')
                                      ->whereIn('task_id', $task_ids)
                                      ->get()->getResult();
                        foreach ($weights as $w) {
                            $task_weights[$w->task_id] = $w->weekly_weights;
                        }
                    }
                }

                $now = strtotime(date("Y-m-d"));
                
                foreach ($paged_data as &$t) {
                    $t['planned_progress'] = 0;
                    $t['actual_progress'] = 0;
                    $t['deviation'] = 0;
                    
                    $pid = $t['project_id'] ?? 0;
                    $tid = $t['id'] ?? 0;
                    $start_date = $project_start_dates[$pid] ?? null;
                    
                    $current_week = 1;
                    if ($start_date && $start_date !== '0000-00-00') {
                        $start = strtotime(date("Y-m-d", strtotime($start_date)));
                        $diff = $now - $start;
                        if ($diff >= 0) {
                            $day_diff = floor($diff / 86400);
                            $current_week = floor($day_diff / 7) + 1;
                        }
                    }
                    
                    if (isset($task_weights[$tid]) && !empty($task_weights[$tid])) {
                        $weekly_data = json_decode($task_weights[$tid], true);
                        if (is_array($weekly_data)) {
                            foreach ($weekly_data as $ww) {
                                $week_num = isset($ww['week_number']) ? (int)$ww['week_number'] : 0;
                                if ($week_num > 0 && $week_num <= $current_week) {
                                    $t['planned_progress'] += (float)($ww['weight'] ?? 0);
                                    $t['actual_progress'] += (float)($ww['actual'] ?? 0);
                                }
                            }
                        }
                    }
                    
                    // Deviation = Actual - Plan
                    $t['deviation'] = $t['actual_progress'] - $t['planned_progress'];
                }
                unset($t);
            }
            // ---------------------------------------------

            return $this->respond([
                "success" => true,
                "data" => $paged_data,
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

            $data = $this->tasks_model->get_details(['id' => $id])->getRow();
            return $this->respond($data);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}