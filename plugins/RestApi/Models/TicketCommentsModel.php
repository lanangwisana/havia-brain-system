<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class TicketCommentsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$ticket_comments_table = $this->db->prefixTable('ticket_comments');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $ticket_comments_table  
        WHERE $ticket_comments_table.deleted=0 AND $ticket_comments_table.description LIKE '%$search%'
        ORDER BY $ticket_comments_table.created_at DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
