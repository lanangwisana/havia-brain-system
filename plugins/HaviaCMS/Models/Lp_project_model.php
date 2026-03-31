<?php

namespace HaviaCMS\Models;

use App\Models\Crud_model;

class Lp_project_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'lp_projects';
        parent::__construct($this->table);
    }

    function get_all_active() {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        return $db->table($prefix . 'lp_projects')
            ->where('deleted', 0)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResult();
    }

    function get_by_category($category_id) {
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        return $db->table($prefix . 'lp_projects')
            ->where('deleted', 0)
            ->where('category_id', $category_id)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResult();
    }

    function get_with_images($id) {
        $project = $this->get_one($id);
        if ($project && $project->id) {
            $img_model = model('HaviaCMS\Models\Lp_project_image_model');
            $project->project_images = $img_model->get_by_project($id);
        }
        return $project;
    }

    function get_all_with_images() {
        $projects = $this->get_all_active();
        $img_model = model('HaviaCMS\Models\Lp_project_image_model');
        foreach ($projects as &$project) {
            $project->project_images = $img_model->get_by_project($project->id);
        }
        return $projects;
    }
}
