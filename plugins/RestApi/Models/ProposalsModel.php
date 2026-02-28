<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class ProposalsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$proposals_table = $this->db->prefixTable('proposals');

		if ($search) {
			$search = $this->db->escapeLikeString($search);
		}

		$sql = "SELECT *
        FROM $proposals_table  
        WHERE $proposals_table.deleted=0
             AND(
                    $proposals_table.note LIKE '%$search%' 
                    OR $proposals_table.content LIKE ('%$search%')
                )
        ORDER BY $proposals_table.proposal_date DESC
        LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
