<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class OrdersModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$orders_table = $this->db->prefixTable('orders');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $orders_table  
        WHERE $orders_table.deleted=0 AND $orders_table.note LIKE '%$search%'
        ORDER BY $orders_table.order_date DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
