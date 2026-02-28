<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class MilestonesModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$milestones_table = $this->db->prefixTable('milestones');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $milestones_table  
        WHERE $milestones_table.deleted=0 AND ($milestones_table.title LIKE '%$search%' OR $milestones_table.description LIKE '%$search%')
        ORDER BY $milestones_table.due_date DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
