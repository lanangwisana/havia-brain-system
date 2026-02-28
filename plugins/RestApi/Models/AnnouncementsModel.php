<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class AnnouncementsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$announcements_table = $this->db->prefixTable('announcements');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $announcements_table  
        WHERE $announcements_table.deleted=0 AND ($announcements_table.title LIKE '%$search%' OR $announcements_table.description LIKE '%$search%')
        ORDER BY $announcements_table.created_at DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
