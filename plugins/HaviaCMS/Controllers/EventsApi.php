<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

// Kita extends ResourceController untuk fitur API yang lengkap
class EventsApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
    protected $settings_model;
    protected $events_model;
    protected $users_model;
    protected $api_settings_model;
    protected $initialized = false;

    public function __construct() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, authtoken, Accept, Origin, X-Requested-With");
        
        if (strtoupper(request()->getMethod()) === 'OPTIONS') {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

    private function _init() {
        if ($this->initialized) return;
        
        helper(['date_time', 'general', 'app_files', 'url']);
        
        if (file_exists(PLUGINPATH . "RestApi/Helpers/jwt_helper.php")) {
            require_once PLUGINPATH . "RestApi/Helpers/jwt_helper.php";
        }
        
        $this->settings_model = model('App\Models\Settings_model');
        $this->events_model = model('App\Models\Events_model');
        $this->users_model = model('App\Models\Users_model');
        $this->api_settings_model = model('RestApi\Models\Api_settings_model');

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
        $all_headers = $this->request->getHeaders();
        $token_raw = null;
        
        foreach($all_headers as $name => $header) {
            if (strtolower($name) === 'authtoken' || strtolower($name) === 'authorization') {
                $token_raw = (string)$header;
                break;
            }
        }

        if (!$token_raw) return "MISSING_TOKEN";

        $token = $token_raw;
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        // JWT Strategy
        try {
            $jwt_config = new \RestApi\Config\JWT();
            $key = preg_replace('/^["\']|["\']$/', '', trim($jwt_config->jwt_key));
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, $jwt_config->jwt_algorithm));
            
            if ($decoded && isset($decoded->id)) return (int)$decoded->id;
            if ($decoded && isset($decoded->crm_user_id)) return (int)$decoded->crm_user_id;
        } catch (\Exception $e) {}

        // DB Fallback
        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row && $user_row->id) return (int)$user_row->id;
        }

        return "INVALID_TOKEN";
    }

    public function index() {
        $this->_init();
        try {
            $user_id = $this->_validate_user();
            if (!is_int($user_id)) {
                return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized: " . $user_id]);
            }

            $user_row = $this->users_model->get_one($user_id);
            if (!$user_row || $user_row->deleted) {
                return $this->response->setStatusCode(404)->setJSON(["success" => false, "message" => "User not found."]);
            }
            
            $this->_load_settings($user_id);
            
            $type = $this->request->getGet('type');
            $label_id = $this->request->getGet('label_id');
            $all_data = [];

            // Log untuk debug (Opsional, tapi membantu)
            // error_log("EventsApi Index - Type: $type, Label: $label_id");

            // 1. Dapatkan Standar EVENTS 
            // HANYA jika type kosong, 'event', atau 'all'
            if (empty($type) || $type === 'event' || $type === 'all') {
                $options = [
                    'login_user_id' => $user_id,
                    'user_id' => $user_id,
                    'team_ids' => isset($user_row->team_ids) ? $user_row->team_ids : "",
                    'type' => 'event',
                    'label_id' => $label_id
                ];
                $events = $this->events_model->get_details($options)->getResult();
                foreach($events as $e) {
                    $e->event_source = 'event'; 
                    $all_data[] = $e;
                }
            }

            // 2. Dapatkan TASKS (Jika type berkaitan dengan task atau 'all')
            if ($type === 'task_start_date' || $type === 'task_deadline' || $type === 'all') {
                $tasks_model = model('App\Models\Tasks_model');
                // Filter ketat: Hanya yang di-assign ke user ini atau sebagai collaborator
                $options = ['specific_user_id' => $user_id]; 
                $tasks = $tasks_model->get_details($options)->getResult();
                
                foreach($tasks as $t) {
                    if ($type === 'task_start_date' || $type === 'all') {
                        if ($t->start_date && $t->start_date !== '0000-00-00') {
                            $all_data[] = (object)[
                                'id' => 'task_s_' . $t->id,
                                'title' => $t->title,
                                'start_date' => $t->start_date,
                                'start_time' => '08:00',
                                'end_time' => '09:00',
                                'description' => $t->description,
                                'color' => '#3498db',
                                'event_source' => 'task_start',
                                'location' => $t->project_title ?? 'Task',
                                'created_by_name' => $t->assigned_to_user ?? 'Havia System'
                            ];
                        }
                    }
                    if ($type === 'task_deadline' || $type === 'all') {
                        if ($t->deadline && $t->deadline !== '0000-00-00') {
                            $all_data[] = (object)[
                                'id' => 'task_d_' . $t->id,
                                'title' => $t->title,
                                'start_date' => $t->deadline,
                                'start_time' => '17:00',
                                'end_time' => '18:00',
                                'description' => $t->description,
                                'color' => '#e74c3c',
                                'event_source' => 'task_deadline',
                                'location' => $t->project_title ?? 'Task',
                                'created_by_name' => $t->assigned_to_user ?? 'Havia System'
                            ];
                        }
                    }
                }
            }

            // 3. Dapatkan PROJECTS (Jika type berkaitan dengan project)
            if ($type === 'project_start_date' || $type === 'project_deadline' || $type === 'all') {
                $projects_model = model('App\Models\Projects_model');
                // Filter ketat: Hanya project dimana user adalah member
                $options = ['user_id' => $user_id]; 
                $projects = $projects_model->get_details($options)->getResult();

                foreach($projects as $p) {
                    if ($type === 'project_start_date' || $type === 'all') {
                        if ($p->start_date && $p->start_date !== '0000-00-00') {
                            $all_data[] = (object)[
                                'id' => 'proj_s_' . $p->id,
                                'title' => $p->title,
                                'start_date' => $p->start_date,
                                'start_time' => '08:00',
                                'end_time' => '09:00',
                                'description' => $p->description,
                                'color' => '#2ecc71',
                                'event_source' => 'project_start',
                                'location' => $p->company_name ?? 'Internal Project',
                                'created_by_name' => 'Project Manager'
                            ];
                        }
                    }
                    if ($type === 'project_deadline' || $type === 'all') {
                        if ($p->deadline && $p->deadline !== '0000-00-00') {
                            $all_data[] = (object)[
                                'id' => 'proj_d_' . $p->id,
                                'title' => $p->title,
                                'start_date' => $p->deadline,
                                'start_time' => '17:00',
                                'end_time' => '18:00',
                                'description' => $p->description,
                                'color' => '#9b59b6',
                                'event_source' => 'project_deadline',
                                'location' => $p->company_name ?? 'Internal Project',
                                'created_by_name' => 'Project Manager'
                            ];
                        }
                    }
                }
            }

            // Sort by Priority (Event > Project > Task) then by Date
            usort($all_data, function($a, $b) {
                // Priority Mapping
                $priority = [
                    'event' => 1,
                    'project_start' => 2,
                    'project_deadline' => 2,
                    'task_start' => 3,
                    'task_deadline' => 3
                ];

                $pA = $priority[$a->event_source] ?? 99;
                $pB = $priority[$b->event_source] ?? 99;

                if ($pA !== $pB) {
                    return $pA - $pB;
                }

                return strtotime($a->start_date) - strtotime($b->start_date);
            });

            return $this->respond($all_data, 200);
            
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get available labels for events
     */
    public function labels() {
        $this->_init();
        try {
            $user_id = $this->_validate_user();
            if (!is_int($user_id)) {
                return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized"]);
            }

            $labels_model = model('App\Models\Labels_model');
            $options = ['context' => 'event'];
            $list_data = $labels_model->get_details($options)->getResult();

            return $this->respond($list_data, 200);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error fetching labels: ' . $e->getMessage()
            ]);
        }
    }
}
