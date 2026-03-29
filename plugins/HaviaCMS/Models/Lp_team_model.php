<?php

namespace HaviaCMS\Models;

use App\Models\Crud_model;

class Lp_team_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'lp_team_members';
        parent::__construct($this->table);
    }

    function get_all_active() {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        return $db->table($prefix . 'lp_team_members')
            ->where('deleted', 0)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResult();
    }
}
