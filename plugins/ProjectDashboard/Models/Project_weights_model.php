<?php

namespace ProjectDashboard\Models;

use App\Models\Crud_model;

class Project_weights_model extends Crud_model
{
    protected $table = null;
    protected $allowedFields = ['project_id', 'task_id', 'item_name', 'nominal_rab', 'start_date', 'end_date', 'weight_percentage', 'weekly_weights', 'approval_status', 'pending_weekly_weights', 'pending_nominal_rab', 'task_ids', 'sort_order', 'deleted', 'created_at'];

    function __construct()
    {
        $this->table = 'pd_project_weights';
        parent::__construct($this->table);
    }

    /**
     * Get weighting items for a project
     */
    function get_details($options = array())
    {
        $weight_table = $this->db->prefixTable('pd_project_weights');
        $tasks_table = $this->db->prefixTable('tasks');
        $project_id = get_array_value($options, "project_id");
        $task_id = get_array_value($options, "task_id");
        
        $where = "";
        if ($project_id) {
            $where .= " AND $weight_table.project_id=$project_id";
        }
        if ($task_id) {
            $where .= " AND $weight_table.task_id=$task_id";
        }

        $sql = "SELECT $weight_table.*
        FROM $weight_table
        LEFT JOIN $tasks_table ON $tasks_table.id = $weight_table.task_id
        WHERE $weight_table.deleted=0 AND $tasks_table.deleted=0 $where
        ORDER BY $weight_table.sort_order ASC";

        return $this->db->query($sql);
    }

    /**
     * Get automatically calculated status based on plan dates
     */
    function get_plan_status_info($start_date, $end_date, $fallback_title = "Unknown", $fallback_color = "#888")
    {
        // 1. Prioritaskan status aktual jika sudah selesai (Done) di modul Project
        if (strtolower(trim($fallback_title)) === "done" || strtolower(trim($fallback_title)) === "selesai" || $fallback_color === "#00b393") {
            return array("title" => $fallback_title, "color" => $fallback_color);
        }

        if (empty($start_date) && empty($end_date)) {
            return array("title" => $fallback_title, "color" => $fallback_color);
        }

        $today = date("Y-m-d");

        if (!empty($start_date) && $today < $start_date) {
            return array("title" => "To do", "color" => "#83c340"); // Default Perfex/system color for To Do
        } else if (!empty($end_date) && $today > $end_date) {
            return array("title" => "Done", "color" => "#00b393"); // System color for Done
        } else {
            return array("title" => "In progress", "color" => "#2d9cdb"); // System color for In Progress
        }
    }
}
