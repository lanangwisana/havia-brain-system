<?php

namespace ProjectDashboard\Models;

use App\Models\Crud_model;

class Weekly_schedules_model extends Crud_model
{
    protected $table = null;
    protected $allowedFields = ['project_id', 'week_number', 'planned_percentage', 'cumulative_planned', 'deleted'];

    function __construct()
    {
        $this->table = 'pd_weekly_schedules';
        parent::__construct($this->table);
    }

    /**
     * Get planned schedules for a project
     */
    function get_details($options = array())
    {
        $schedules_table = $this->db->prefixTable('pd_weekly_schedules');
        $project_id = get_array_value($options, "project_id");
        $where = "";
        if ($project_id) {
            $where .= " AND $schedules_table.project_id=$project_id";
        }

        $sql = "SELECT $schedules_table.*
        FROM $schedules_table
        WHERE $schedules_table.deleted=0 $where
        ORDER BY $schedules_table.week_number ASC";

        return $this->db->query($sql);
    }
}
