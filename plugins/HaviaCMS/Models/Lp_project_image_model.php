<?php

namespace HaviaCMS\Models;

use App\Models\Crud_model;

class Lp_project_image_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'lp_project_images';
        parent::__construct($this->table);
    }

    function get_by_project($project_id) {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        return $db->table($prefix . 'lp_project_images')
            ->where('deleted', 0)
            ->where('project_id', $project_id)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResult();
    }

    function delete_by_project($project_id) {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        $db->table($prefix . 'lp_project_images')
            ->where('project_id', $project_id)
            ->update(['deleted' => 1]);
    }
}
