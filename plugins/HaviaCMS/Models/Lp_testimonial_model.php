<?php

namespace HaviaCMS\Models;

use App\Models\Crud_model;

class Lp_testimonial_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'lp_testimonials';
        parent::__construct($this->table);
    }

    function get_all_active() {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        return $db->table($prefix . 'lp_testimonials')
            ->where('deleted', 0)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResult();
    }

    function get_by_type($type) {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        return $db->table($prefix . 'lp_testimonials')
            ->where('deleted', 0)
            ->where('type', $type)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResult();
    }

    function count_by_type($type) {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        return $db->table($prefix . 'lp_testimonials')
            ->where('deleted', 0)
            ->where('type', $type)
            ->countAllResults();
    }
}
