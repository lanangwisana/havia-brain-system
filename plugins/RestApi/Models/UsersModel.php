<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class UsersModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$users_table = $this->db->prefixTable('users');

		if ($search) {
			$search = $this->db->escapeLikeString($search);
		}

		$sql = "SELECT *
        FROM $users_table  
        WHERE $users_table.deleted=0
             AND(
                    $users_table.first_name LIKE '%$search%' 
                    OR $users_table.last_name LIKE ('%$search%')
                    OR $users_table.email LIKE ('%$search%')
                    OR $users_table.phone LIKE ('%$search%')
                    OR $users_table.job_title LIKE ('%$search%')
                    OR CONCAT($users_table.first_name, ' ', $users_table.last_name) LIKE ('%$search%')
                )
        ORDER BY $users_table.first_name ASC
        LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
