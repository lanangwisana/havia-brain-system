<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class ToDoModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$to_do_table = $this->db->prefixTable('to_do');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $to_do_table  
        WHERE $to_do_table.deleted=0 AND ($to_do_table.title LIKE '%$search%' OR $to_do_table.description LIKE '%$search%')
        ORDER BY $to_do_table.created_at DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
