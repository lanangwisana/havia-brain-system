<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class EstimatesModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$estimates_table = $this->db->prefixTable('estimates');

		if ($search) {
			$search = $this->db->escapeLikeString($search);
		}

		$sql = "SELECT *
        FROM $estimates_table  
        WHERE $estimates_table.deleted=0
             AND(
                    $estimates_table.note LIKE '%$search%' 
                )
        ORDER BY $estimates_table.estimate_date DESC
        LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
