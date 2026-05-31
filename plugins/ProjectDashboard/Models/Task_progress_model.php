<?php

namespace ProjectDashboard\Models;

use App\Models\Crud_model;

class Task_progress_model extends Crud_model
{
    protected $table = null;
    protected $allowedFields = ['project_id', 'task_id', 'week_number', 'physical_percentage', 'deleted', 'created_at'];

    function __construct()
    {
        $this->table = 'pd_task_progress';
        parent::__construct($this->table);
    }

    /**
     * Get task progress records for a project
     */
    function get_details($options = array())
    {
        $prog_table = $this->db->prefixTable('pd_task_progress');
        $tasks_table = $this->db->prefixTable('tasks');
        $project_id = get_array_value($options, "project_id");
        $week_number = get_array_value($options, "week_number");
        
        $where = "";
        if ($project_id) {
            $where .= " AND $prog_table.project_id=$project_id";
        }
        if ($week_number) {
            $where .= " AND $prog_table.week_number=$week_number";
        }

        $sql = "SELECT $prog_table.*
        FROM $prog_table
        LEFT JOIN $tasks_table ON $tasks_table.id = $prog_table.task_id
        WHERE $prog_table.deleted=0 AND $tasks_table.deleted=0 $where
        ORDER BY $prog_table.task_id ASC, $prog_table.week_number ASC";

        return $this->db->query($sql);
    }
}
