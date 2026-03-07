<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class PaymentMethodsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$payment_methods_table = $this->db->prefixTable('payment_methods');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $payment_methods_table  
        WHERE $payment_methods_table.deleted=0 AND $payment_methods_table.title LIKE '%$search%'
        ORDER BY $payment_methods_table.title ASC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
