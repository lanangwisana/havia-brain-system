<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class MessagesModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$messages_table = $this->db->prefixTable('messages');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $messages_table  
        WHERE $messages_table.deleted=0 AND ($messages_table.subject LIKE '%$search%' OR $messages_table.message LIKE '%$search%')
        ORDER BY $messages_table.created_at DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
