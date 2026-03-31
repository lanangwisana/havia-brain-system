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

            $user = $this->users_model->get_one($user_id);

            $project_id = $this->request->getGet('project_id');
            $status_filter = strtoupper($this->request->getGet('status') ?? 'ALL');
            $page = (int)($this->request->getGet('page') ?? 1);
            $limit = 5; // Fixed limit as per previous standard
            
            $options = [];
            if ($project_id) {
                // Saat masuk detail project: Bisa lihat SEMUA task project ini
                $options['project_id'] = $project_id;
                
                // Lapisan keamanan: pastikan non-admin benaran member/ada keterlibatan
                if (!$user->is_admin) {
                    $projects_model = model('App\Models\Projects_model');
                    $p_check = $projects_model->get_details(['id' => $project_id, 'user_id' => $user_id])->getRow();
                    
                    if (!$p_check) {
                        // Cek adakah tugas nyasar (Deep Discovery)
                        $t_check = $this->tasks_model->get_details(['project_id' => $project_id, 'specific_user_id' => $user_id])->getRow();
                        if (!$t_check) {
                            return $this->response->setStatusCode(403)->setJSON(["success" => false, "message" => "Anda tidak berhak mengakses tugas untuk project ini."]);
                        }
                    }
                }
            } else {
                // Module All Tasks: Hanya task "My Tasks"
                $options['specific_user_id'] = $user_id;
            }
            
            // Note: RISE tasks get_details often defaults to open tasks only if no status is provided
            if ($status_filter === 'ALL') {
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
                $all_tasks = array_filter($all_tasks, function($t) use ($status_filter) {
                    $st = strtoupper($t['status_title'] ?? $t['status'] ?? '');
                    $sid = (int)($t['status_id'] ?? 0);
                    
                    if ($status_filter === 'DONE') {
                        return ($st === 'DONE' || $st === 'COMPLETED' || $st === 'SELESAI' || $sid === 3);
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
            usort($all_tasks, function($a, $b) {
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
