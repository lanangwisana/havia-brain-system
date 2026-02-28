<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class ContractsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$contracts_table = $this->db->prefixTable('contracts');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $contracts_table  
        WHERE $contracts_table.deleted=0 AND ($contracts_table.title LIKE '%$search%' OR $contracts_table.note LIKE '%$search%')
        ORDER BY $contracts_table.contract_date DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
