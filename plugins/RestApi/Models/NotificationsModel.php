<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class NotificationsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$notifications_table = $this->db->prefixTable('notifications');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $notifications_table  
        WHERE ($notifications_table.title LIKE '%$search%' OR $notifications_table.event LIKE '%$search%')
        ORDER BY $notifications_table.created_at DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
