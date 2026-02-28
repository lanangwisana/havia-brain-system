<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class ExpensesModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$expenses_table = $this->db->prefixTable('expenses');

		if ($search) {
			$search = $this->db->escapeLikeString($search);
		}

		$sql = "SELECT *
        FROM $expenses_table  
        WHERE $expenses_table.deleted=0
             AND(
                    $expenses_table.title LIKE '%$search%' 
                    OR $expenses_table.description LIKE ('%$search%')
                )
        ORDER BY $expenses_table.expense_date DESC
        LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
