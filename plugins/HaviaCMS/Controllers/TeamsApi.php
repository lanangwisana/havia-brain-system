<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class TeamsApi extends ResourceController
{
    use ResponseTrait;

    protected $format = 'json';
    protected $users_model;
    protected $api_settings_model;
    private $initialized = false;

    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, authtoken, Accept, Origin, X-Requested-With");

        if (strtoupper(request()->getMethod()) === 'OPTIONS') {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

    private function _init()
    {
        if ($this->initialized) return;

        helper(['general', 'app_files', 'url']);

        $this->users_model = model('App\Models\Users_model');
        $this->api_settings_model = model('RestApi\Models\Api_settings_model');

        $this->initialized = true;
    }

    private function _validate_admin()
    {
        $all_headers = $this->request->getHeaders();
        $token_raw = null;

        foreach ($all_headers as $name => $header) {
            if (strtolower($name) === 'authtoken' || strtolower($name) === 'authorization') {
                $token_raw = (string) $header;
                break;
            }
        }

        if (!$token_raw) return "MISSING_TOKEN";

        $token = $token_raw;
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        $user_id = null;

        try {
            $jwt_config = new \RestApi\Config\JWT();
            $key = preg_replace('/^["\']|["\']$/', '', trim($jwt_config->jwt_key));
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, $jwt_config->jwt_algorithm));

            if ($decoded && isset($decoded->id)) $user_id = (int) $decoded->id;
            else if ($decoded && isset($decoded->crm_user_id)) $user_id = (int) $decoded->crm_user_id;
        } catch (\Exception $e) {}

        if (!$user_id) {
            $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
            if ($api_user && isset($api_user->user)) {
                $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
                if ($user_row && $user_row->id) $user_id = (int) $user_row->id;
            }
        }

        if ($user_id) {
            $user_info = $this->users_model->get_one($user_id);
            $is_admin_role = false;
            
            if ($user_info && $user_info->is_admin) {
                $is_admin_role = true;
            } else if ($user_info && $user_info->role_id) {
                $roles_model = model('App\Models\Roles_model');
                $role = $roles_model->get_one($user_info->role_id);
                if ($role && stripos($role->title, 'admin') !== false) {
                    $is_admin_role = true;
                }
            }

            if ($is_admin_role) {
                return $user_id;
            }
            return "NOT_ADMIN";
        }

        return "INVALID_TOKEN";
    }

    public function index()
    {
        $this->_init();
        $user_id = $this->_validate_admin();
        if (!is_int($user_id)) {
            return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized"]);
        }

        $options = ["user_type" => "staff", "status" => "active", "deleted" => 0];
        $members = $this->users_model->get_details($options)->getResult();

        $data = [];
        foreach ($members as $m) {
            $data[] = [
                "id" => $m->id,
                "first_name" => $m->first_name,
                "last_name" => $m->last_name,
                "name" => $m->first_name . " " . $m->last_name,
                "image" => $m->image,
                "job_title" => $m->job_title,
                "role_title" => $m->role_title,
                "is_admin" => $m->is_admin
            ];
        }

        return $this->respond(["success" => true, "data" => $data]);
    }

    public function summary($id)
    {
        $this->_init();
        $user_id = $this->_validate_admin();
        if (!is_int($user_id)) {
            return $this->response->setStatusCode(401)->setJSON(["success" => false, "message" => "Unauthorized"]);
        }

        try {
            $member = $this->users_model->get_details(["id" => $id, "user_type" => "staff"])->getRow();
            if (!$member) {
                return $this->response->setStatusCode(404)->setJSON(["success" => false, "message" => "User not found."]);
            }

            $biodata = [
                "id" => $member->id,
                "name" => $member->first_name . " " . $member->last_name,
                "email" => $member->email,
                "phone" => $member->phone,
                "job_title" => $member->job_title,
                "role_title" => $member->role_title,
                "image" => $member->image,
                "gender" => $member->gender,
                "alternative_phone" => $member->alternative_phone ?? null,
                "dob" => $member->dob ?? null,
                "ssn" => $member->ssn ?? null,
                "address" => $member->address ?? null,
                "alternative_address" => $member->alternative_address ?? null
            ];

            $db = \Config\Database::connect();
            
            $builder = $db->table('leave_applications');
            $builder->where('applicant_id', $id);
            $builder->where('deleted', 0);
            $builder->where('status', 'approved');
            $total_leaves = $builder->countAllResults();

            $total_salary = isset($member->salary) ? (float) $member->salary : 0;
            $salary_term = isset($member->salary_term) ? $member->salary_term : '';

            $tasks_builder = $db->table('tasks t');
            $tasks_builder->select('t.id, t.title, t.status_id, ts.title as status_title, p.title as project_title, t.deadline');
            $tasks_builder->join('task_status ts', 't.status_id = ts.id', 'left');
            $tasks_builder->join('projects p', 't.project_id = p.id', 'left');
            $tasks_builder->where("(t.assigned_to = $id OR FIND_IN_SET($id, t.collaborators))");
            $tasks_builder->where('t.deleted', 0);
            $tasks_builder->where("LOWER(ts.title) != 'completed'");
            
            // Deadline within the next 7 days (including today)
            $now = date('Y-m-d');
            $next_7_days = date('Y-m-d', strtotime('+7 days'));
            $tasks_builder->where("t.deadline >=", $now);
            $tasks_builder->where("t.deadline <=", $next_7_days);
            
            // Order by closest deadline and limit to 1
            $tasks_builder->orderBy('t.deadline', 'ASC');
            $tasks_builder->limit(1);
            
            $active_tasks = $tasks_builder->get()->getResult();

            return $this->respond([
                "success" => true,
                "data" => [
                    "biodata" => $biodata,
                    "total_leaves" => $total_leaves,
                    "total_salary" => $total_salary,
                    "salary_term" => $salary_term,
                    "active_tasks" => $active_tasks
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                "success" => false, 
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine()
            ]);
        }
    }
}
