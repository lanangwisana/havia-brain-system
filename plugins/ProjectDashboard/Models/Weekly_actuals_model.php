<?php

namespace ProjectDashboard\Models;

use App\Models\Crud_model;

class Weekly_actuals_model extends Crud_model
{
    protected $table = null;
    protected $allowedFields = ['project_id', 'week_number', 'actual_percentage', 'cumulative_actual', 'deviation', 'notes', 'deleted', 'created_at'];

    function __construct()
    {
        $this->table = 'pd_weekly_actuals';
        parent::__construct($this->table);
    }

    /**
     * Get actual history for a project
     */
    function get_details($options = array())
    {
        $actuals_table = $this->db->prefixTable('pd_weekly_actuals');
        $project_id = get_array_value($options, "project_id");
        $where = "";
        if ($project_id) {
            $where .= " AND $actuals_table.project_id=$project_id";
        }

        $sql = "SELECT $actuals_table.*
        FROM $actuals_table
        WHERE $actuals_table.deleted=0 $where
        ORDER BY $actuals_table.week_number ASC";

        return $this->db->query($sql);
    }
}
