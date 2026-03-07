<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class AttendanceModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$attendance_table = $this->db->prefixTable('attendance');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $attendance_table  
        WHERE $attendance_table.deleted=0 AND $attendance_table.note LIKE '%$search%'
        ORDER BY $attendance_table.in_time DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
