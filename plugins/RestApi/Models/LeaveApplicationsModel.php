<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class LeaveApplicationsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$leave_applications_table = $this->db->prefixTable('leave_applications');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $leave_applications_table  
        WHERE $leave_applications_table.deleted=0 AND $leave_applications_table.reason LIKE '%$search%'
        ORDER BY $leave_applications_table.created_at DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
