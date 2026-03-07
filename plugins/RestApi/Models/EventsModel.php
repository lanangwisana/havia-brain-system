<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class EventsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$events_table = $this->db->prefixTable('events');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $events_table  
        WHERE $events_table.deleted=0 AND ($events_table.title LIKE '%$search%' OR $events_table.description LIKE '%$search%')
        ORDER BY $events_table.start_date DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
