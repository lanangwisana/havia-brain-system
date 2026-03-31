<?php

namespace HaviaCMS\Models;

use App\Models\Crud_model;

class Lp_request_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'lp_portfolio_requests';
        parent::__construct($this->table);
    }

    function get_all_active() {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        return $db->table($prefix . 'lp_portfolio_requests')
            ->where('deleted', 0)
            ->orderBy('created_at', 'DESC')
            ->get()->getResult();
    }

    function mark_sent($id) {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        $db->table($prefix . 'lp_portfolio_requests')
            ->where('id', $id)
            ->update(['status' => 'sent', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    function get_pending_count() {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        return $db->table($prefix . 'lp_portfolio_requests')
            ->where('deleted', 0)
            ->where('status', 'pending')
            ->countAllResults();
    }
}
