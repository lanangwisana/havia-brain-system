<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class InvoicePaymentsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$invoice_payments_table = $this->db->prefixTable('invoice_payments');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $invoice_payments_table  
        WHERE $invoice_payments_table.deleted=0 AND $invoice_payments_table.note LIKE '%$search%'
        ORDER BY $invoice_payments_table.payment_date DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
