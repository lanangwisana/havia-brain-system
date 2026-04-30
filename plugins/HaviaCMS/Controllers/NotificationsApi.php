<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class NotificationsApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
    protected $tasks_model;
    protected $projects_model;
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
        
        $this->tasks_model = model('App\Models\Tasks_model');
        $this->projects_model = model('App\Models\Projects_model');
        $this->api_settings_model = model('RestApi\Models\Api_settings_model');
        $this->users_model = model('App\Models\Users_model');
        $this->settings_model = model('App\Models\Settings_model');

        $this->initialized = true;
    }

    private function _validate_user() {
        $token_raw = null;
        $all_headers = $this->request->getHeaders();
        foreach($all_headers as $name => $header) {
            if (strtolower($name) === 'authtoken' || strtolower($name) === 'authorization') {
                $token_raw = (string)$header;
                break;
            }
        }
        if (!$token_raw) return false;

        $token = $token_raw;
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        // JWT Validation
        try {
            $jwt_config = new \RestApi\Config\JWT();
            $key = preg_replace('/^["\']|["\']$/', '', trim($jwt_config->jwt_key));
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, $jwt_config->jwt_algorithm));
            if ($decoded && (isset($decoded->id) || isset($decoded->crm_user_id))) {
                return (int)($decoded->id ?? $decoded->crm_user_id);
            }
        } catch (\Exception $e) {}

        // Fallback DB
        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row) return (int)$user_row->id;
        }

        return false;
    }

    public function index() {
        try {
            $this->_init();
            $user_id = $this->_validate_user();
            if (!$user_id) return $this->failUnauthorized("Token tidak valid.");

            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $last_week = date('Y-m-d H:i:s', strtotime('-7 days'));
            
            $tasks_table = $this->tasks_model->db->prefixTable('tasks');
            $projects_table = $this->projects_model->db->prefixTable('projects');
            $members_table = $this->projects_model->db->prefixTable('project_members');

            // 1. Task Deadlines (Exactly Today or Tomorrow)
            // Exclude if created_date is the same as deadline (to avoid alerts for tasks created today for today/tomorrow)
            $deadline_tasks_sql = "SELECT t.id, t.title, t.deadline, 'task' as module, t.project_id, p.title as project_title 
                                   FROM $tasks_table t
                                   LEFT JOIN $projects_table p ON p.id = t.project_id
                                   WHERE t.deleted=0 AND t.status_id != 3 
                                   AND (t.assigned_to=$user_id OR FIND_IN_SET('$user_id', t.collaborators))
                                   AND (DATE(t.deadline) = '$today' OR DATE(t.deadline) = '$tomorrow')
                                   AND DATE(t.created_date) != DATE(t.deadline)
                                   ORDER BY t.deadline ASC LIMIT 15";
            $task_items = $this->tasks_model->db->query($deadline_tasks_sql)->getResultArray();

            foreach ($task_items as $task) {
                $item_date = date('Y-m-d', strtotime($task['deadline']));
                $isToday = $item_date == $today;
                
                $notifications[] = [
                    'id' => 'task_dl_' . $task['id'],
                    'type' => 'deadline',
                    'module' => 'task',
                    'title' => $isToday ? "Due Today" : "Due Tomorrow",
                    'message' => 'Task "' . $task['title'] . '" needs immediate attention',
                    'date' => $task['deadline'],
                    'target_id' => $task['id'],
                    'project_id' => $task['project_id'],
                    'project_title' => $task['project_title'],
                    'severity' => $isToday ? 'urgent' : 'warning'
                ];
            }

            // 2. Project Deadlines (Exactly Today or Tomorrow)
            $deadline_proj_sql = "SELECT p.id, p.title, p.deadline FROM $projects_table p
                                  INNER JOIN $members_table pm ON pm.project_id = p.id
                                  WHERE p.deleted=0 AND pm.deleted=0 AND pm.user_id=$user_id
                                  AND p.status_id IN (1,3)
                                  AND (DATE(p.deadline) = '$today' OR DATE(p.deadline) = '$tomorrow')
                                  AND DATE(p.created_date) != DATE(p.deadline)
                                  ORDER BY p.deadline ASC LIMIT 10";
            $proj_items = $this->projects_model->db->query($deadline_proj_sql)->getResultArray();

            foreach ($proj_items as $project) {
                $item_date = date('Y-m-d', strtotime($project['deadline']));
                $isToday = $item_date == $today;
                $notifications[] = [
                    'id' => 'project_dl_' . $project['id'],
                    'type' => 'deadline',
                    'module' => 'project',
                    'title' => $isToday ? "Due Today" : "Due Tomorrow",
                    'message' => 'Project "' . $project['title'] . '" approaching deadline',
                    'date' => $project['deadline'],
                    'target_id' => $project['id'],
                    'severity' => $isToday ? 'urgent' : 'warning'
                ];
            }

            // Sort all by date ASC (Today first, then Tomorrow)
            usort($notifications, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });

            return $this->respond([
                "success" => true,
                "data" => $notifications
            ]);

        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}