<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class TasksModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$tasks_table = $this->db->prefixTable('tasks');

		if ($search) {
			$search = $this->db->escapeLikeString($search);
		}

		$sql = "SELECT *
        FROM $tasks_table  
        WHERE $tasks_table.deleted=0
             AND(
                    $tasks_table.title LIKE '%$search%' 
                    OR $tasks_table.description LIKE ('%$search%')
                )
        ORDER BY $tasks_table.created_date DESC
        LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
