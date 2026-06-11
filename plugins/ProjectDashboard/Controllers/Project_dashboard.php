<?php

namespace ProjectDashboard\Controllers;

use App\Controllers\Security_Controller;

class Project_dashboard extends Security_Controller
{

    protected $Project_weights_model;
    protected $Weekly_schedules_model;
    protected $Weekly_actuals_model;

    function __construct()
    {
        parent::__construct();
        if ($this->has_all_projects_restricted_role()) {
            app_redirect("forbidden");
        }
        // Model inti seperti Tasks_model & Projects_model sudah dimuat otomatis oleh parent
        $this->Project_weights_model = model("ProjectDashboard\Models\Project_weights_model");
        $this->Weekly_schedules_model = model("ProjectDashboard\Models\Weekly_schedules_model");
        $this->Weekly_actuals_model = model("ProjectDashboard\Models\Weekly_actuals_model");
    }

    /**
     * Halaman Utama: Daftar Proyek dengan Ringkasan S-Curve
     */
    function index()
    {
        try {
            $db = \Config\Database::connect();
            $table_name = $db->prefixTable('pd_project_weights');
            if ($db->tableExists($table_name)) {
                $fields = $db->getFieldNames($table_name);
                if (!in_array('approval_status', $fields)) {
                    $db->query("ALTER TABLE $table_name ADD COLUMN approval_status VARCHAR(20) DEFAULT 'Approved'");
                }
                if (!in_array('pending_weekly_weights', $fields)) {
                    $db->query("ALTER TABLE $table_name ADD COLUMN pending_weekly_weights TEXT NULL");
                }
                if (!in_array('pending_nominal_rab', $fields)) {
                    $db->query("ALTER TABLE $table_name ADD COLUMN pending_nominal_rab DECIMAL(20,2) NULL");
                }
            }
            $options = array(
                "status_id" => 1, // Active projects only
            );

            // Jika tidak memiliki izin mengelola semua proyek, hanya tampilkan proyek di mana user menjadi anggota
            if (!$this->can_manage_all_projects()) {
                $options["user_id"] = $this->login_user->id;
            }

            $list_res = $this->Projects_model->get_details($options);
            $list_data = $list_res ? $list_res->getResult() : array();

            // Urutkan project berdasarkan ID terbaru (paling atas)
            usort($list_data, function ($a, $b) {
                return (int) $b->id - (int) $a->id;
            });

            $projects = array();
            $total_actual_progress = 0;
            $deviating_count = 0;

            foreach ($list_data as $data) {
                $row = $this->_make_project_summary_row($data);
                $projects[] = $row;

                $total_actual_progress += $row->actual_progress;
                if ($row->deviation < -5) {
                    $deviating_count++;
                }
            }

            $view_data['projects'] = $projects;

            // Data untuk Widget
            $view_data['total_projects'] = count($projects);
            $view_data['avg_progress'] = $view_data['total_projects'] > 0 ? ($total_actual_progress / $view_data['total_projects']) : 0;
            $view_data['deviating_projects'] = $deviating_count;

            // Estimasi minggu berjalan dari rata-rata tanggal mulai
            $view_data['current_period'] = "Week " . $this->_get_average_week($projects);

            // Fetch pending approvals for Admin & HR/Admin Projek
            $view_data['pending_approvals'] = array();
            if ($this->can_manage_all_projects()) {
                $pending_query = $db->query("SELECT w.*, p.title as project_title, t.title as task_title FROM " . $db->prefixTable('pd_project_weights') . " w LEFT JOIN " . $db->prefixTable('projects') . " p ON p.id = w.project_id LEFT JOIN " . $db->prefixTable('tasks') . " t ON t.id = w.task_id WHERE w.approval_status = 'Pending' AND w.deleted=0");
                $view_data['pending_approvals'] = $pending_query ? $pending_query->getResult() : array();
            }

            return $this->template->rander('ProjectDashboard\Views\index', $view_data);
        } catch (\Exception $e) {
            die("Dashboard Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        }
    }

    private function _get_average_week($projects)
    {
        if (count($projects) == 0)
            return 1;
        $total_week = 0;
        foreach ($projects as $p) {
            $total_week += $p->week;
        }
        return ceil($total_week / count($projects));
    }

    /**
     * Mempersiapkan data baris ringkasan proyek
     */
    private function _make_project_summary_row($data)
    {
        $project_id = $data->id;

        // Calculate actual progress based on weighting items
        $actual_progress = $this->_calculate_actual_progress($project_id);

        // Get planned progress for current week
        $current_week = $this->_get_current_week_number($data->start_date);
        $planned_progress = $this->_get_planned_progress($project_id, $current_week);

        // Calculate total deviation as the sum of (weekly actual - weekly planned) for all weeks
        $db = \Config\Database::connect();
        $planned_res = $db->table($db->prefixTable('pd_weekly_schedules'))->where('project_id', $project_id)->get()->getResult();
        $actual_res = $db->table($db->prefixTable('pd_weekly_actuals'))->where('project_id', $project_id)->get()->getResult();

        $actual_map = array();
        foreach ($actual_res as $act) {
            $actual_map[$act->week_number] = (float) $act->actual_percentage;
        }

        $deviation = 0;
        foreach ($planned_res as $plan) {
            if ($plan->week_number <= $current_week) {
                $act_val = isset($actual_map[$plan->week_number]) ? $actual_map[$plan->week_number] : 0.0;
                $deviation += ($act_val - (float)$plan->planned_percentage);
            }
        }

        $status = "On Schedule";
        if ($deviation < -5) {
            $status = "Delay (" . number_format($deviation, 1) . "%)";
        } else if ($deviation < 0) {
            $status = "Behind (" . number_format($deviation, 1) . "%)";
        }

        return (object) array(
            "id" => $project_id,
            "title" => $data->title,
            "client_name" => $data->company_name,
            "price" => $data->price,
            "actual_progress" => $actual_progress,
            "planned_progress" => $planned_progress,
            "status" => $status,
            "deviation" => $deviation,
            "week" => $current_week
        );
    }

    /**
     * Halaman Detail: Kurva S & Pembobotan
     */
    function view($project_id = 0)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        if (!$project_id) {
            show_404();
        }

        try {
            $project_info = $this->Projects_model->get_details(array("id" => $project_id))->getRow();
        } catch (\Exception $e) {
            die("Database Error in Projects_model: " . $e->getMessage());
        }
        if (!$project_info) {
            show_404();
        }

        $view_data['project_info'] = $project_info;

        try {
            // Auto-synchronize weights, planned schedules, and actuals from Project module before fetching
            $this->_sync_project_weights_with_tasks($project_id);
            $this->_recalculate_project_weights($project_id);
            $this->_generate_weekly_schedule($project_id);
            $this->_generate_weekly_actuals($project_id);

            $weights_res = $this->Project_weights_model->get_details(array("project_id" => $project_id));
            $view_data['weights'] = $weights_res ? $weights_res->getResult() : array();

            $planned_res = $this->Weekly_schedules_model->get_details(array("project_id" => $project_id));
            $view_data['planned_schedules'] = $planned_res ? $planned_res->getResult() : array();

            $actual_res = $this->Weekly_actuals_model->get_details(array("project_id" => $project_id));
            $view_data['actual_history'] = $actual_res ? $actual_res->getResult() : array();
        } catch (\Exception $e) {
            die("Database Error in Plugin Models: " . $e->getMessage());
        }

        // Calculate current state
        $view_data['current_actual'] = $this->_calculate_actual_progress($project_id);

        // Fetch all tasks for this project to map titles and status
        $tasks_res = $this->Tasks_model->get_details(array("project_id" => $project_id))->getResult();
        $tasks_map = array();
        $parent_tasks = array();
        $sub_tasks_map = array();

        foreach ($tasks_res as $t) {
            $tasks_map[$t->id] = $t;
            if ($t->parent_task_id) {
                $sub_tasks_map[$t->parent_task_id][] = $t;
            } else {
                $parent_tasks[] = $t;
            }
        }

        $view_data['tasks_map'] = $tasks_map;
        $view_data['parent_tasks'] = $parent_tasks;

        // Sort subtasks within each parent by start_date (earliest first)
        foreach ($sub_tasks_map as $parent_id => &$children) {
            usort($children, function ($a, $b) {
                $a_date = (isset($a->start_date) && $a->start_date) ? $a->start_date : null;
                $b_date = (isset($b->start_date) && $b->start_date) ? $b->start_date : null;
                
                // Tasks without start_date go to the end
                if (!$a_date && !$b_date) return 0;
                if (!$a_date) return 1;
                if (!$b_date) return -1;
                
                return strcmp($a_date, $b_date);
            });
        }
        unset($children); // Break the reference

        $view_data['sub_tasks_map'] = $sub_tasks_map;

        $total_project_price = (float) ($project_info->price ?? 0);
        $total_rab = 0;

        $weights_map = array();
        $nominal_rab_map = array();
        $start_date_map = array();
        $end_date_map = array();
        $plan_status_map = array();
        $approval_status_map = array();
        $reject_reason_map = array();

        foreach ($view_data['weights'] as $w) {
            $total_rab += (float) $w->nominal_rab;
            if ($w->task_id) {
                $tid = $w->task_id;
                $weights_map[$tid] = (float) $w->weight_percentage;
                $nominal_rab_map[$tid] = (float) $w->nominal_rab;
                $approval_status_map[$tid] = isset($w->approval_status) ? $w->approval_status : 'Approved';
                $reject_reason_map[$tid] = isset($w->reject_reason) ? $w->reject_reason : null;
                
                $t_start = isset($tasks_map[$tid]->start_date) ? $tasks_map[$tid]->start_date : null;
                $t_end = isset($tasks_map[$tid]->deadline) ? $tasks_map[$tid]->deadline : null;
                
                $start_date_map[$tid] = $t_start ? format_to_date($t_start, false) : "-";
                $end_date_map[$tid] = $t_end ? format_to_date($t_end, false) : "-";

                $fallback_title = isset($tasks_map[$tid]) ? $tasks_map[$tid]->status_title : "Unknown";
                $fallback_color = isset($tasks_map[$tid]) ? $tasks_map[$tid]->status_color : "#888";
                $plan_status_map[$tid] = $this->Project_weights_model->get_plan_status_info($t_start, $t_end, $fallback_title, $fallback_color);
            } else if ($w->task_ids) {
                // Legacy support
                $tids = explode(',', $w->task_ids);
                foreach ($tids as $tid) {
                    $weights_map[trim($tid)] = (float) $w->weight_percentage;
                }
            }
        }

        if ($total_project_price == 0) {
            $total_project_price = $total_rab;
        }

        $view_data['weights_map'] = $weights_map;
        $view_data['nominal_rab_map'] = $nominal_rab_map;
        $view_data['start_date_map'] = $start_date_map;
        $view_data['end_date_map'] = $end_date_map;
        $view_data['plan_status_map'] = $plan_status_map;
        $view_data['approval_status_map'] = $approval_status_map;
        $view_data['reject_reason_map'] = $reject_reason_map;

        $weights_obj_map = array();
        foreach ($view_data['weights'] as $w) {
            if ($w->task_id) {
                $weights_obj_map[$w->task_id] = $w;
            }
        }

        $actual_progress_map = array();
        foreach ($tasks_res as $t) {
            $actual_progress_map[$t->id] = $this->_get_task_actual_progress($t->id, $weights_obj_map, $tasks_map, $sub_tasks_map);
        }
        $view_data['actual_progress_map'] = $actual_progress_map;
        $view_data['total_project_price'] = $total_project_price;

        // Build actual_status_map based on weekly_weights data
        // To Do = no weekly_weights yet, In Progress = has weights but plan != actual, Done = all plan == actual
        $actual_status_map = array();
        foreach ($tasks_res as $t) {
            $tid = $t->id;
            $w = isset($weights_obj_map[$tid]) ? $weights_obj_map[$tid] : null;
            $has_weekly_weights = ($w && !empty($w->weekly_weights) && is_array(json_decode($w->weekly_weights, true)) && count(json_decode($w->weekly_weights, true)) > 0);

            if (isset($sub_tasks_map[$tid]) && !$has_weekly_weights) {
                // Parent task without its own weekly weights: aggregate status from children
                $child_statuses = array();
                foreach ($sub_tasks_map[$tid] as $child) {
                    $child_statuses[] = $this->_determine_actual_status($child->id, $weights_obj_map, $tasks_map);
                }
                if (empty($child_statuses) || (count(array_unique($child_statuses)) === 1 && $child_statuses[0] === 'To Do')) {
                    $actual_status_map[$tid] = 'To Do';
                } else if (in_array('To Do', $child_statuses) || in_array('In Progress', $child_statuses)) {
                    $actual_status_map[$tid] = 'In Progress';
                } else {
                    $actual_status_map[$tid] = 'Done';
                }
            } else {
                $actual_status_map[$tid] = $this->_determine_actual_status($tid, $weights_obj_map, $tasks_map);
            }
        }
        $view_data['actual_status_map'] = $actual_status_map;

        // Calculate total weight and total nominal RAB of leaf nodes for the footer
        $total_weight = 0;
        $total_leaf_rab = 0;
        foreach ($tasks_res as $t) {
            // A node is a leaf if it has no sub-tasks (its ID is not a parent to any other task)
            if (!isset($sub_tasks_map[$t->id])) {
                $total_weight += isset($weights_map[$t->id]) ? $weights_map[$t->id] : 0;
                $total_leaf_rab += isset($nominal_rab_map[$t->id]) ? $nominal_rab_map[$t->id] : 0;
            }
        }
        $view_data['total_weight'] = $total_weight;
        $view_data['total_leaf_rab'] = $total_leaf_rab;

        $current_week = $this->_get_current_week_number($project_info->start_date);
        $view_data['current_planned'] = $this->_get_planned_progress($project_id, $current_week);
        $view_data['current_week'] = $current_week;
        $view_data['can_edit_project_weights'] = $this->can_manage_all_projects();

        try {
            return $this->template->rander('ProjectDashboard\Views\view', $view_data);
        } catch (\Exception $e) {
            die("View/Template Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        }
    }

    function activity_log($project_id = 0)
    {
        if (!$project_id) {
            show_404();
        }

        try {
            $project_info = $this->Projects_model->get_details(array("id" => $project_id))->getRow();
        } catch (\Exception $e) {
            die("Database Error: " . $e->getMessage());
        }
        if (!$project_info) {
            show_404();
        }

        $view_data['project_info'] = $project_info;

        // Pagination
        $page = $this->request->getGet('page') ? (int) $this->request->getGet('page') : 1;
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $db = \Config\Database::connect();
        $logs_table = $db->prefixTable('pd_actual_activity_logs');
        $users_table = $db->prefixTable('users');

        // Get total count
        $count_query = $db->query("SELECT COUNT(*) as total FROM $logs_table WHERE project_id = ?", array($project_id));
        $total_rows = $count_query ? (int) $count_query->getRow()->total : 0;

        // Get paginated data
        $limit = (int) $limit;
        $offset = (int) $offset;
        $logs_query = $db->query("
            SELECT $logs_table.*, CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_user, $users_table.image AS created_by_avatar
            FROM $logs_table
            LEFT JOIN $users_table ON $users_table.id = $logs_table.created_by
            WHERE $logs_table.project_id = ?
            ORDER BY $logs_table.created_at DESC
            LIMIT $limit OFFSET $offset
        ", array($project_id));

        $view_data['activity_logs'] = $logs_query ? $logs_query->getResult() : array();
        
        // Pager variables
        $view_data['page'] = $page;
        $view_data['total_pages'] = ceil($total_rows / $limit);
        $view_data['project_id'] = $project_id;

        return $this->template->rander('ProjectDashboard\Views\activity_log', $view_data);
    }

    /**
     * Logika utama penghitungan progres aktual berdasarkan Task terhubung
     */
    private function _get_task_actual_progress($task_id, $weights_map, $tasks_map, $sub_tasks_map)
    {
        $w = isset($weights_map[$task_id]) ? $weights_map[$task_id] : null;
        
        // 1. Check if the task itself has manual actual weights in JSON
        if ($w && !empty($w->weekly_weights)) {
            $manual_weights = json_decode($w->weekly_weights, true);
            if (is_array($manual_weights) && count($manual_weights) > 0) {
                $sum_actual = 0;
                $has_actual_key = false;
                foreach ($manual_weights as $item) {
                    if (isset($item['actual'])) {
                        $has_actual_key = true;
                        $sum_actual += (float) $item['actual'];
                    }
                }
                if ($has_actual_key) {
                    return $sum_actual;
                }
            }
        }

        // 2. If it is a parent task with child tasks, sum the children's actual progress
        if (isset($sub_tasks_map[$task_id])) {
            $sum_children_actual = 0;
            foreach ($sub_tasks_map[$task_id] as $sub_task) {
                $sum_children_actual += $this->_get_task_actual_progress($sub_task->id, $weights_map, $tasks_map, $sub_tasks_map);
            }
            return $sum_children_actual;
        }

        // 3. Fallback for leaf task: 100% of weight if status is Done (3), otherwise 0
        if (isset($tasks_map[$task_id]) && $tasks_map[$task_id]->status_id == 3) {
            return $w ? (float) $w->weight_percentage : 0;
        }

        return 0;
    }

    /**
     * Determine the "actual status" of a leaf task based on weekly_weights data.
     * - To Do:        No weekly_weights data, or total actual = 0
     * - In Progress:  Total actual > 0 but < total plan (benchmark bobot)
     * - Done:         Total actual >= total plan
     */
    private function _determine_actual_status($task_id, $weights_obj_map, $tasks_map = array())
    {
        $w = isset($weights_obj_map[$task_id]) ? $weights_obj_map[$task_id] : null;

        // No weight record or no weekly_weights → Fallback to system status, otherwise To Do
        if (!$w || empty($w->weekly_weights)) {
            if (isset($tasks_map[$task_id]) && $tasks_map[$task_id]->status_id == 3) {
                return 'Done';
            }
            return 'To Do';
        }

        $weekly = json_decode($w->weekly_weights, true);
        if (!is_array($weekly) || empty($weekly)) {
            if (isset($tasks_map[$task_id]) && $tasks_map[$task_id]->status_id == 3) {
                return 'Done';
            }
            return 'To Do';
        }

        // Sum total plan and total actual across all weeks
        $total_plan = 0;
        $total_actual = 0;
        foreach ($weekly as $item) {
            $total_plan += isset($item['weight']) ? (float) $item['weight'] : (isset($item['plan']) ? (float) $item['plan'] : 0);
            $total_actual += isset($item['actual']) ? (float) $item['actual'] : 0;
        }

        // To Do: actual belum diisi sama sekali
        if ($total_actual < 0.001) {
            return 'To Do';
        }

        // Done: actual sudah >= plan (benchmark bobot)
        if ($total_actual >= $total_plan - 0.001) {
            return 'Done';
        }

        // In Progress: actual > 0 tapi belum mencapai plan
        return 'In Progress';
    }

    private function _calculate_actual_progress($project_id)
    {
        $weights_res = $this->Project_weights_model->get_details(array("project_id" => $project_id));
        $weights = $weights_res ? $weights_res->getResult() : array();

        $weights_map = array();
        foreach ($weights as $w) {
            $weights_map[$w->task_id] = $w;
        }

        $tasks_res = $this->Tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $tasks_map = array();
        $parent_tasks = array();
        $sub_tasks_map = array();

        foreach ($tasks_res as $t) {
            $tasks_map[$t->id] = $t;
            if ($t->parent_task_id) {
                $sub_tasks_map[$t->parent_task_id][] = $t;
            } else {
                $parent_tasks[] = $t;
            }
        }

        $total_actual = 0;

        foreach ($parent_tasks as $task) {
            $total_actual += $this->_get_task_actual_progress($task->id, $weights_map, $tasks_map, $sub_tasks_map);
        }

        return (float) $total_actual;
    }

    private function _count_completed_tasks($task_ids)
    {
        if (empty($task_ids))
            return 0;

        $db = \Config\Database::connect();
        $builder = $db->table($db->prefixTable('tasks'));
        $builder->whereIn('id', $task_ids);
        $builder->where('status_id', 3); // 3 = Done
        $builder->where('deleted', 0);
        return $builder->countAllResults();
    }

    private function _get_current_week_number($start_date)
    {
        if (!$start_date)
            return 1;
        $start = strtotime(date("Y-m-d", strtotime($start_date)));
        $now = strtotime(date("Y-m-d"));
        $diff = $now - $start;
        if ($diff < 0)
            return 1;
        $day_diff = floor($diff / 86400);
        $week = floor($day_diff / 7) + 1;
        return $week > 0 ? $week : 1;
    }

    private function _get_planned_progress($project_id, $week_number)
    {
        $row = $this->Weekly_schedules_model->get_one_where(array("project_id" => $project_id, "week_number" => $week_number, "deleted" => 0));
        if ($row && $row->id) {
            return $row->cumulative_planned;
        }

        $db = \Config\Database::connect();
        $builder = $db->table($db->prefixTable('pd_weekly_schedules'));
        $builder->where('project_id', $project_id);
        $builder->where('week_number <=', $week_number);
        $builder->where('deleted', 0);
        $builder->orderBy('week_number', 'DESC');
        $res = $builder->get()->getRow();

        return $res ? $res->cumulative_planned : 0;
    }

    /**
     * Hapus Data Pembobotan (Soft Delete)
     */
    function delete_weight()
    {
        if (!$this->can_manage_all_projects()) {
            return $this->response->setJSON(array("success" => false, 'message' => "Unauthorized"));
        }
        $id = $this->request->getPost('id');
        if ($id && $this->Project_weights_model->delete($id)) {
            return $this->response->setJSON(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            return $this->response->setJSON(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    // ============================================
    // RAB Edit and Planning Automation
    // ============================================

    function modal_edit_rab()
    {
        if (!$this->can_manage_all_projects()) {
            die("Unauthorized");
        }
        $task_id = $this->request->getPost('task_id');
        $project_id = $this->request->getPost('project_id');

        // Cek data saat ini
        $model_info = $this->Project_weights_model->get_one_where(array("task_id" => $task_id, "project_id" => $project_id, "deleted" => 0));

        $project_info = $this->Projects_model->get_one($project_id);

        $view_data['task_id'] = $task_id;
        $view_data['project_id'] = $project_id;
        $view_data['model_info'] = $model_info;
        $view_data['project_deadline'] = $project_info->deadline;
        $view_data['login_user'] = $this->login_user;

        return $this->template->view('ProjectDashboard\Views\modal_edit_rab', $view_data);
    }
    function save_rab_weight()
    {
        if (!$this->can_manage_all_projects()) {
            return $this->response->setJSON(array("success" => false, 'message' => "Unauthorized"));
        }
        try {
            $task_id = $this->request->getPost('task_id');
            $project_id = $this->request->getPost('project_id');
            $nominal_rab = $this->request->getPost('nominal_rab');

            $data = array(
                "project_id" => $project_id,
                "task_id" => $task_id
            );

            // Get task info for item_name
            $task_info = $this->Tasks_model->get_one($task_id);
            if ($task_info) {
                $data["item_name"] = $task_info->title;
            }

            // Check if exists
            $existing = $this->Project_weights_model->get_one_where(array("task_id" => $task_id, "project_id" => $project_id, "deleted" => 0));
            $existing_id = !empty($existing->id) ? $existing->id : 0;

            $new_val = $nominal_rab ? (float) $nominal_rab : 0;

            // Enforce limit: Total nominal RAB must not exceed Project Contract Value (if set)
            $project_info = $this->Projects_model->get_one($project_id);
            $contract_value = (isset($project_info->price) && (float) $project_info->price > 0) ? (float) $project_info->price : 0;

            if ($contract_value > 0) {
                // Find parent task IDs (which are auto-zeroed in recalculation)
                $db = \Config\Database::connect();
                $tasks_table = $db->prefixTable('tasks');
                $parent_query = $db->query("SELECT DISTINCT parent_task_id FROM $tasks_table WHERE project_id=$project_id AND parent_task_id > 0 AND deleted=0");
                $parent_ids = array();
                if ($parent_query) {
                    foreach ($parent_query->getResult() as $row) {
                        $parent_ids[] = $row->parent_task_id;
                    }
                }

                // Calculate total RAB of other tasks
                $weights = $this->Project_weights_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
                $total_other_rab = 0;
                foreach ($weights as $w) {
                    if (in_array($w->task_id, $parent_ids)) {
                        continue;
                    }
                    if ($w->task_id == $task_id) {
                        continue;
                    }
                    $total_other_rab += (float) $w->nominal_rab;
                }

                $proposed_total = $total_other_rab + $new_val;
                if ($proposed_total > $contract_value) {
                    return $this->response->setJSON(array(
                        "success" => false, 
                        "message" => "Total nominal RAB melebihi Contract Value"
                    ));
                }
            }

            $current_rab = $existing ? (float) $existing->nominal_rab : 0;
            $new_val = $nominal_rab ? (float) $nominal_rab : 0;

            if ($this->can_manage_all_projects()) {
                $data["nominal_rab"] = $new_val;
                $data["approval_status"] = 'Approved';
                $data["pending_nominal_rab"] = NULL;
                if ($new_val != $current_rab) {
                    $data["weekly_weights"] = NULL;
                }
            } else {
                if ($new_val == $current_rab) {
                    $data["nominal_rab"] = $new_val;
                    $data["approval_status"] = 'Approved';
                    $data["pending_nominal_rab"] = NULL;
                } else {
                    $data["pending_nominal_rab"] = $new_val;
                    $data["approval_status"] = 'Pending';
                }
            }

            $save_id = $this->Project_weights_model->ci_save($data, $existing_id);

            if ($save_id) {
                if ($this->can_manage_all_projects()) {
                    // Recalculate all weights and regenerate schedule
                    $this->_recalculate_project_weights($project_id);
                    $this->_generate_weekly_schedule($project_id);
                    $this->_generate_weekly_actuals($project_id);
                }

                return $this->response->setJSON(array("success" => true, 'message' => app_lang('record_saved')));
            } else {
                return $this->response->setJSON(array("success" => false, 'message' => app_lang('error_occurred')));
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(array("success" => false, 'message' => "Error: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine()));
        }
    }    function modal_edit_parent_dates()
    {
        if (!$this->can_manage_all_projects()) {
            die("Unauthorized");
        }
        $task_id = $this->request->getPost('task_id');
        $project_id = $this->request->getPost('project_id');

        $task_info = $this->Tasks_model->get_one($task_id);
        $project_info = $this->Projects_model->get_one($project_id);
        $weight_info = $this->Project_weights_model->get_one_where(array("task_id" => $task_id, "project_id" => $project_id, "deleted" => 0));

        // Check if it is a parent task (has child tasks)
        $db = \Config\Database::connect();
        $tasks_table = $db->prefixTable('tasks');
        $child_query = $db->query("SELECT id FROM $tasks_table WHERE parent_task_id=$task_id AND deleted=0");
        $child_tasks = $child_query ? $child_query->getResult() : array();

        $weight_percentage = 0;
        if (count($child_tasks) > 0) {
            $child_ids = array();
            foreach ($child_tasks as $ct) {
                $child_ids[] = $ct->id;
            }
            $weights_query = $db->query("SELECT SUM(weight_percentage) as total_weight FROM " . $db->prefixTable('pd_project_weights') . " WHERE task_id IN (" . implode(',', $child_ids) . ") AND deleted=0");
            $weights_res = $weights_query ? $weights_query->getRow() : null;
            $weight_percentage = $weights_res ? (float) $weights_res->total_weight : 0;
        } else {
            $weight_percentage = $weight_info ? (float) $weight_info->weight_percentage : 0;
        }

        $t_start = isset($task_info->start_date) ? $task_info->start_date : null;
        $t_end = isset($task_info->deadline) ? $task_info->deadline : null;

        // If it is a parent task, its start and end dates are the min start date and max end date of its children
        if (count($child_tasks) > 0) {
            $child_ids = array();
            foreach ($child_tasks as $ct) {
                $child_ids[] = $ct->id;
            }
            $dates_query = $db->query("SELECT MIN(start_date) as min_start, MAX(deadline) as max_end FROM " . $db->prefixTable('tasks') . " WHERE id IN (" . implode(',', $child_ids) . ") AND deleted=0");
            $dates_res = $dates_query ? $dates_query->getRow() : null;
            if ($dates_res) {
                if ($dates_res->min_start) {
                    $t_start = $dates_res->min_start;
                }
                if ($dates_res->max_end) {
                    $t_end = $dates_res->max_end;
                }
            }
        }

        if (empty($t_start)) {
            $t_start = $project_info->start_date;
        }
        if (empty($t_end)) {
            $t_end = $project_info->deadline;
        }

        $start_week = $this->_get_current_week_number_for_date($project_info->start_date, $t_start);
        $end_week = $this->_get_current_week_number_for_date($project_info->start_date, $t_end);

        if ($start_week < 1) {
            $start_week = 1;
        }
        if ($end_week < $start_week) {
            $end_week = $start_week;
        }

        $view_data['task_id'] = $task_id;
        $view_data['project_id'] = $project_id;
        $view_data['task_info'] = $task_info;
        $view_data['project_deadline'] = $project_info->deadline;
        $view_data['weekly_weights'] = ($weight_info && $weight_info->weekly_weights) ? json_decode($weight_info->weekly_weights, true) : array();
        $view_data['weight_percentage'] = $weight_percentage;
        $view_data['start_week'] = $start_week;
        $view_data['end_week'] = $end_week;
        $view_data['login_user'] = $this->login_user;

        return $this->template->view('ProjectDashboard\Views\modal_edit_parent_dates', $view_data);
    }

    function save_parent_dates()
    {
        if (!$this->can_manage_all_projects()) {
            return $this->response->setJSON(array("success" => false, 'message' => "Unauthorized"));
        }
        try {
            $task_id = $this->request->getPost('task_id');
            $project_id = $this->request->getPost('project_id');
            
            $weight_info = $this->Project_weights_model->get_one_where(array("task_id" => $task_id, "project_id" => $project_id, "deleted" => 0));

            // Extract old actual weights by week number before update
            $old_actuals = array();
            if ($weight_info && $weight_info->weekly_weights) {
                $old_weekly_weights = json_decode($weight_info->weekly_weights, true);
                if (is_array($old_weekly_weights)) {
                    foreach ($old_weekly_weights as $item) {
                        $wk_num = isset($item['week_number']) ? (int) $item['week_number'] : 1;
                        $old_actuals[$wk_num] = isset($item['actual']) ? (float) $item['actual'] : 0.0;
                    }
                }
            }

            // Check if it is a parent task (has child tasks)
            $db = \Config\Database::connect();
            $tasks_table = $db->prefixTable('tasks');
            $child_query = $db->query("SELECT id FROM $tasks_table WHERE parent_task_id=$task_id AND deleted=0");
            $child_tasks = $child_query ? $child_query->getResult() : array();

            $benchmark_weight = 0;
            if (count($child_tasks) > 0) {
                $child_ids = array();
                foreach ($child_tasks as $ct) {
                    $child_ids[] = $ct->id;
                }
                $weights_query = $db->query("SELECT SUM(weight_percentage) as total_weight FROM " . $db->prefixTable('pd_project_weights') . " WHERE task_id IN (" . implode(',', $child_ids) . ") AND deleted=0");
                $weights_res = $weights_query ? $weights_query->getRow() : null;
                $benchmark_weight = $weights_res ? (float) $weights_res->total_weight : 0;
            } else {
                $benchmark_weight = $weight_info ? (float) $weight_info->weight_percentage : 0;
            }

            $weekly_weeks = $this->request->getPost('weekly_weeks') ?: array();
            $weekly_values = $this->request->getPost('weekly_values') ?: array();
            $weekly_actuals = $this->request->getPost('weekly_actuals') ?: array();

            $total_weight = 0;
            $total_actual = 0;
            $weekly_data = array();
            for ($i = 0; $i < count($weekly_weeks); $i++) {
                if (isset($weekly_weeks[$i]) && isset($weekly_values[$i])) {
                    $val = (float) $weekly_values[$i];
                    $total_weight += $val;
                    
                    $act_val = isset($weekly_actuals[$i]) && $weekly_actuals[$i] !== '' ? (float) $weekly_actuals[$i] : 0.0;
                    $total_actual += $act_val;
                    
                    $week_name = trim($weekly_weeks[$i]);
                    preg_match('/\d+/', $week_name, $matches);
                    $week_number = isset($matches[0]) ? (int) $matches[0] : 1;

                    $weekly_data[] = array(
                        "week_name" => $week_name,
                        "week_number" => $week_number,
                        "weight" => $val,
                        "actual" => $act_val
                    );
                }
            }

            // Benchmark validation
            if (abs($total_weight - $benchmark_weight) >= 0.01) {
                return $this->response->setJSON(array(
                    "success" => false, 
                    "message" => "Total bobot rencana mingguan (" . number_format($total_weight, 2) . "%) harus sama dengan bobot total pekerjaan (" . number_format($benchmark_weight, 2) . "%)."
                ));
            }

            if ($total_actual > $benchmark_weight + 0.01) {
                return $this->response->setJSON(array(
                    "success" => false, 
                    "message" => "Total bobot aktual mingguan (" . number_format($total_actual, 2) . "%) tidak boleh melebihi bobot total pekerjaan (" . number_format($benchmark_weight, 2) . "%)."
                ));
            }

            if ($weight_info && $weight_info->id) {
                $save_data = array(
                    "weekly_weights" => json_encode($weekly_data),
                    "approval_status" => 'Approved',
                    "pending_weekly_weights" => NULL
                );
                $this->Project_weights_model->ci_save($save_data, $weight_info->id);

                // Insert activity logs for any actual progress changes
                $task_info = $this->Tasks_model->get_one($task_id);
                $task_title = $task_info ? $task_info->title : 'Unknown Task';
                foreach ($weekly_data as $item) {
                    $week_number = $item['week_number'];
                    $new_val = (float) $item['actual'];
                    $old_val = isset($old_actuals[$week_number]) ? (float) $old_actuals[$week_number] : 0.0;
                    if (abs($new_val - $old_val) >= 0.0001) {
                        $log_data = array(
                            "project_id" => $project_id,
                            "task_id" => $task_id,
                            "task_title" => $task_title,
                            "week_number" => $week_number,
                            "old_actual" => $old_val,
                            "new_actual" => $new_val,
                            "created_by" => $this->login_user->id,
                            "created_at" => date("Y-m-d H:i:s")
                        );
                        $db->table($db->prefixTable('pd_actual_activity_logs'))->insert($log_data);
                    }
                }
                
                // Regenerate weekly schedule and actuals
                $this->_generate_weekly_schedule($project_id);
                $this->_generate_weekly_actuals($project_id);

                return $this->response->setJSON(array("success" => true, 'message' => app_lang('record_saved')));
            } else {
                return $this->response->setJSON(array("success" => false, 'message' => app_lang('error_occurred')));
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(array("success" => false, 'message' => "Error: " . $e->getMessage()));
        }
    }

    function approve_rab()
    {
        if (!$this->can_manage_all_projects()) {
            return $this->response->setJSON(array("success" => false, 'message' => "Unauthorized"));
        }

        try {
            $task_id = $this->request->getPost('task_id');
            $project_id = $this->request->getPost('project_id');
            
            $weight_info = $this->Project_weights_model->get_one_where(array("task_id" => $task_id, "project_id" => $project_id, "deleted" => 0));

            if ($weight_info && $weight_info->id && $weight_info->pending_nominal_rab !== NULL) {
                $new_val = (float) $weight_info->pending_nominal_rab;

                // Validate limit before approving
                $project_info = $this->Projects_model->get_one($project_id);
                $contract_value = (isset($project_info->price) && (float) $project_info->price > 0) ? (float) $project_info->price : 0;

                if ($contract_value > 0) {
                    // Find parent task IDs (which are auto-zeroed in recalculation)
                    $db = \Config\Database::connect();
                    $tasks_table = $db->prefixTable('tasks');
                    $parent_query = $db->query("SELECT DISTINCT parent_task_id FROM $tasks_table WHERE project_id=$project_id AND parent_task_id > 0 AND deleted=0");
                    $parent_ids = array();
                    if ($parent_query) {
                        foreach ($parent_query->getResult() as $row) {
                            $parent_ids[] = $row->parent_task_id;
                        }
                    }

                    // Calculate total RAB of other tasks
                    $weights = $this->Project_weights_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
                    $total_other_rab = 0;
                    foreach ($weights as $w) {
                        if (in_array($w->task_id, $parent_ids)) {
                            continue;
                        }
                        if ($w->task_id == $task_id) {
                            continue;
                        }
                        $total_other_rab += (float) $w->nominal_rab;
                    }

                    $proposed_total = $total_other_rab + $new_val;
                    if ($proposed_total > $contract_value) {
                        return $this->response->setJSON(array(
                            "success" => false, 
                            "message" => "Total nominal RAB melebihi Contract Value"
                        ));
                    }
                }

                $new_val = (float) $weight_info->pending_nominal_rab;
                $current_val = (float) $weight_info->nominal_rab;

                $save_data = array(
                    "nominal_rab" => $weight_info->pending_nominal_rab,
                    "approval_status" => 'Approved',
                    "pending_nominal_rab" => NULL
                );
                if ($new_val != $current_val) {
                    $save_data["weekly_weights"] = NULL;
                }
                $this->Project_weights_model->ci_save($save_data, $weight_info->id);
                
                // Recalculate weights and S-Curve
                $this->_recalculate_project_weights($project_id);
                $this->_generate_weekly_schedule($project_id);
                $this->_generate_weekly_actuals($project_id);

                return $this->response->setJSON(array("success" => true, 'message' => 'Bobot RAB telah disetujui'));
            } else {
                return $this->response->setJSON(array("success" => false, 'message' => 'Data tidak ditemukan atau tidak ada pengajuan'));
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(array("success" => false, 'message' => "Error: " . $e->getMessage()));
        }
    }

    function reject_rab()
    {
        if (!$this->can_manage_all_projects()) {
            return $this->response->setJSON(array("success" => false, 'message' => "Unauthorized"));
        }

        try {
            $task_id = $this->request->getPost('task_id');
            $project_id = $this->request->getPost('project_id');
            $reject_reason = trim($this->request->getPost('reject_reason') ?: '');
            
            $weight_info = $this->Project_weights_model->get_one_where(array("task_id" => $task_id, "project_id" => $project_id, "deleted" => 0));

            if ($weight_info && $weight_info->id) {
                $save_data = array(
                    "approval_status" => 'Rejected',
                    "reject_reason" => $reject_reason ?: NULL,
                );
                $this->Project_weights_model->ci_save($save_data, $weight_info->id);
                return $this->response->setJSON(array("success" => true, 'message' => 'Bobot RAB telah ditolak'));
            } else {
                return $this->response->setJSON(array("success" => false, 'message' => 'Data tidak ditemukan'));
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(array("success" => false, 'message' => "Error: " . $e->getMessage()));
        }
    }

    private function _sync_project_weights_with_tasks($project_id)
    {
        $tasks_res = $this->Tasks_model->get_details(array("project_id" => $project_id))->getResult();
        $weights_res = $this->Project_weights_model->get_details(array("project_id" => $project_id))->getResult();

        $existing_task_ids = array();
        $weights_by_task = array();
        foreach ($weights_res as $w) {
            if ($w->task_id) {
                $existing_task_ids[] = $w->task_id;
                $weights_by_task[$w->task_id] = $w;
            }
        }

        $active_task_ids = array();
        foreach ($tasks_res as $t) {
            $active_task_ids[] = $t->id;
            if (!in_array($t->id, $existing_task_ids)) {
                // Insert new task into weights table
                $data = array(
                    "project_id" => $project_id,
                    "task_id" => $t->id,
                    "item_name" => $t->title,
                    "nominal_rab" => 0
                );
                $this->Project_weights_model->ci_save($data);
            } else {
                // Update item_name if task title changed
                if (isset($weights_by_task[$t->id]) && $weights_by_task[$t->id]->item_name !== $t->title) {
                    $update_weight_data = array("item_name" => $t->title);
                    $this->Project_weights_model->ci_save($update_weight_data, $weights_by_task[$t->id]->id);
                }
            }
        }

        // Check if any task in weights table was deleted from tasks table
        foreach ($weights_res as $w) {
            if ($w->task_id && !in_array($w->task_id, $active_task_ids)) {
                $this->Project_weights_model->delete($w->id);
            }
        }
    }

    private function _recalculate_project_weights($project_id)
    {
        $project_info = $this->Projects_model->get_one($project_id);
        $weights = $this->Project_weights_model->get_details(array("project_id" => $project_id))->getResult();

        // Find parent tasks in the database for this project
        $db = \Config\Database::connect();
        $tasks_table = $db->prefixTable('tasks');
        $parent_query = $db->query("SELECT DISTINCT parent_task_id FROM $tasks_table WHERE project_id=$project_id AND parent_task_id > 0 AND deleted=0");
        $parent_ids = array();
        if ($parent_query) {
            foreach ($parent_query->getResult() as $row) {
                $parent_ids[] = $row->parent_task_id;
            }
        }

        // Auto-zero parent tasks
        foreach ($weights as $w) {
            if (in_array($w->task_id, $parent_ids)) {
                $w->nominal_rab = 0;
                $zero_data = array("nominal_rab" => 0, "weight_percentage" => 0, "start_date" => null, "end_date" => null);
                $this->Project_weights_model->ci_save($zero_data, $w->id);
            }
        }

        $total_rab = 0;
        foreach ($weights as $w) {
            $total_rab += isset($w->nominal_rab) ? (float) $w->nominal_rab : 0;
        }

        $project_price = (isset($project_info->price) && (float) $project_info->price > 0) ? (float) $project_info->price : $total_rab;

        if ($project_price > 0) {
            foreach ($weights as $w) {
                if (in_array($w->task_id, $parent_ids)) {
                    continue; // Skip parents as they are already 0
                }
                $nom = isset($w->nominal_rab) ? (float) $w->nominal_rab : 0;
                $percentage = ($nom / $project_price) * 100;
                $update_data = array("weight_percentage" => $percentage);
                $this->Project_weights_model->ci_save($update_data, $w->id);
            }
        }
    }

    private function _generate_weekly_schedule($project_id)
    {
        $project_info = $this->Projects_model->get_one($project_id);
        if (empty($project_info->start_date))
            return;

        $weights = $this->Project_weights_model->get_details(array("project_id" => $project_id))->getResult();

        $tasks_res = $this->Tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $tasks_map = array();
        foreach ($tasks_res as $t) {
            $tasks_map[$t->id] = $t;
        }

        $weekly_plans = array();

        foreach ($weights as $w) {
            $tid = $w->task_id;
            $t_start = isset($tasks_map[$tid]->start_date) ? $tasks_map[$tid]->start_date : null;
            $t_end = isset($tasks_map[$tid]->deadline) ? $tasks_map[$tid]->deadline : null;
            if (!empty($t_end)) {
                $manual_weights = !empty($w->weekly_weights) ? json_decode($w->weekly_weights, true) : null;
                if (!empty($manual_weights) && is_array($manual_weights)) {
                    $start_date = !empty($t_start) ? $t_start : $project_info->start_date;
                    $start_week = $this->_get_current_week_number_for_date($project_info->start_date, $start_date);
                    $end_week = $this->_get_current_week_number_for_date($project_info->start_date, $t_end);
                    if ($start_week < 1) {
                        $start_week = 1;
                    }
                    if ($end_week < $start_week) {
                        $end_week = $start_week;
                    }

                    $shift = 0;
                    $first_item = reset($manual_weights);
                    $first_stored_week = isset($first_item['week_number']) ? (int) $first_item['week_number'] : 0;
                    if ($first_stored_week > 0 && $first_stored_week < $start_week) {
                        $shift = $start_week - $first_stored_week;
                    }

                    foreach ($manual_weights as $item) {
                        $week_num = isset($item['week_number']) ? (int) $item['week_number'] : 0;
                        if ($week_num > 0) {
                            $week_num += $shift;
                            $w_pct = isset($item['weight']) ? (float) $item['weight'] : 0;
                            if ($w_pct > 0) {
                                if (!isset($weekly_plans[$week_num])) {
                                    $weekly_plans[$week_num] = 0;
                                }
                                $weekly_plans[$week_num] += $w_pct;
                            }
                        }
                    }
                }
            }
        }

        $db = \Config\Database::connect();
        $db->table($db->prefixTable('pd_weekly_schedules'))->where('project_id', $project_id)->delete();

        if (!empty($weekly_plans)) {

            $max_week = max(array_keys($weekly_plans));
            $cumulative = 0;
            for ($w = 1; $w <= $max_week; $w++) {
                $planned_pct = isset($weekly_plans[$w]) ? $weekly_plans[$w] : 0;
                $cumulative += $planned_pct;
                $data = array(
                    "project_id" => $project_id,
                    "week_number" => $w,
                    "planned_percentage" => $planned_pct,
                    "cumulative_planned" => $cumulative
                );
                $this->Weekly_schedules_model->ci_save($data);
            }
        }
    }

    private function _get_current_week_number_for_date($project_start_date, $target_date)
    {
        if (!$project_start_date || !$target_date)
            return 1;
        $start = strtotime(date("Y-m-d", strtotime($project_start_date)));
        $target = strtotime(date("Y-m-d", strtotime($target_date)));
        $diff = $target - $start;
        if ($diff < 0)
            return 1;
        $day_diff = floor($diff / 86400);
        $week = floor($day_diff / 7) + 1;
        return $week > 0 ? $week : 1;
    }

    private function _accumulate_task_weekly_actuals($task_id, $project_start_date, $weights_map, $tasks_map, $sub_tasks_map, &$weekly_actuals)
    {
        $w = isset($weights_map[$task_id]) ? $weights_map[$task_id] : null;
        
        // 1. Check if this task has manual actual weights
        if ($w && !empty($w->weekly_weights)) {
            $manual_weights = json_decode($w->weekly_weights, true);
            if (is_array($manual_weights) && count($manual_weights) > 0) {
                $has_actual_key = false;
                $temp_actuals = array();
                foreach ($manual_weights as $item) {
                    if (isset($item['actual'])) {
                        $has_actual_key = true;
                        $week_num = isset($item['week_number']) ? (int) $item['week_number'] : 0;
                        if ($week_num > 0) {
                            if (!isset($temp_actuals[$week_num])) {
                                $temp_actuals[$week_num] = 0;
                            }
                            $temp_actuals[$week_num] += (float) $item['actual'];
                        }
                    }
                }
                if ($has_actual_key) {
                    // Accumulate manual actuals and return (do not process children to avoid double-counting)
                    foreach ($temp_actuals as $week => $val) {
                        if ($val > 0) {
                            if (!isset($weekly_actuals[$week])) {
                                $weekly_actuals[$week] = 0;
                            }
                            $weekly_actuals[$week] += $val;
                        }
                    }
                    return;
                }
            }
        }

        // 2. If it has child tasks, recurse into children
        if (isset($sub_tasks_map[$task_id])) {
            foreach ($sub_tasks_map[$task_id] as $sub_task) {
                $this->_accumulate_task_weekly_actuals($sub_task->id, $project_start_date, $weights_map, $tasks_map, $sub_tasks_map, $weekly_actuals);
            }
            return;
        }

        // 3. Fallback for completed leaf task: place entire weight on its deadline week
        if (isset($tasks_map[$task_id]) && $tasks_map[$task_id]->status_id == 3) {
            $t_end = $tasks_map[$task_id]->deadline;
            if (!empty($t_end) && $w && (float) $w->weight_percentage > 0) {
                $end_week = $this->_get_current_week_number_for_date($project_start_date, $t_end);
                if ($end_week > 0) {
                    if (!isset($weekly_actuals[$end_week])) {
                        $weekly_actuals[$end_week] = 0;
                    }
                    $weekly_actuals[$end_week] += (float) $w->weight_percentage;
                }
            }
        }
    }

    private function _generate_weekly_actuals($project_id)
    {
        $project_info = $this->Projects_model->get_one($project_id);
        if (empty($project_info->start_date))
            return;

        $current_week = $this->_get_current_week_number($project_info->start_date);

        $weights = $this->Project_weights_model->get_details(array("project_id" => $project_id))->getResult();
        $weights_map = array();
        foreach ($weights as $w) {
            $weights_map[$w->task_id] = $w;
        }

        $tasks_res = $this->Tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $tasks_map = array();
        $parent_tasks = array();
        $sub_tasks_map = array();

        foreach ($tasks_res as $t) {
            $tasks_map[$t->id] = $t;
            if ($t->parent_task_id) {
                $sub_tasks_map[$t->parent_task_id][] = $t;
            } else {
                $parent_tasks[] = $t;
            }
        }

        $weekly_actuals = array();
        foreach ($parent_tasks as $task) {
            $this->_accumulate_task_weekly_actuals($task->id, $project_info->start_date, $weights_map, $tasks_map, $sub_tasks_map, $weekly_actuals);
        }

        // Ambil semua jadwal rencana untuk menghitung deviasi dan mencari minggu maksimum
        $planned_res = $this->Weekly_schedules_model->get_details(array("project_id" => $project_id))->getResult();
        $planned_map = array();
        $max_planned_week = 0;
        foreach ($planned_res as $plan) {
            $planned_map[$plan->week_number] = $plan->cumulative_planned;
            if ($plan->week_number > $max_planned_week) {
                $max_planned_week = $plan->week_number;
            }
        }

        $db = \Config\Database::connect();
        $db->table($db->prefixTable('pd_weekly_actuals'))->where('project_id', $project_id)->delete();

        $cumulative = 0;
        // Simpan riwayat progres aktual untuk seluruh minggu rencana
        for ($w = 1; $w <= $max_planned_week; $w++) {
            $act_pct = isset($weekly_actuals[$w]) ? $weekly_actuals[$w] : 0;
            $cumulative += $act_pct;
            $plan_cum = isset($planned_map[$w]) ? $planned_map[$w] : 0;
            $dev = $cumulative - $plan_cum;

            $data = array(
                "project_id" => $project_id,
                "week_number" => $w,
                "actual_percentage" => $act_pct,
                "cumulative_actual" => $cumulative,
                "deviation" => $dev
            );
            $this->Weekly_actuals_model->ci_save($data);
        }
    }
}



