<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class ActivityLogsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$activity_logs_table = $this->db->prefixTable('activity_logs');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $activity_logs_table  
        WHERE $activity_logs_table.deleted=0 AND $activity_logs_table.log_type_title LIKE '%$search%'
        ORDER BY $activity_logs_table.created_at DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
