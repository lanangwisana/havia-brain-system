<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class NotesModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$notes_table = $this->db->prefixTable('notes');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $notes_table  
        WHERE $notes_table.deleted=0 AND ($notes_table.title LIKE '%$search%' OR $notes_table.description LIKE '%$search%')
        ORDER BY $notes_table.created_at DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
