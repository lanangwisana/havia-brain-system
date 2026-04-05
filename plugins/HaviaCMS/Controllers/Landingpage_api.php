<?php

namespace HaviaCMS\Controllers;

use App\Controllers\App_Controller;

class Landingpage_api extends App_Controller {

    function __construct() {
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        // Ensure landing page tables exist
        havia_create_lp_tables();
    }

    /**
     * GET /api/haviacms/landingpage/settings
     * Returns all landing page content for the frontend
     */
    function settings() {
        if ($this->request->getMethod() === "options") {
            return $this->response->setJSON(["status" => "ok"]);
        }

        // --- Text settings from settings table ---
        $keys = array(
            "landingpage_hero_label", "landingpage_hero_btn1", "landingpage_hero_btn2",
            "landingpage_about_accent", "landingpage_about_h2", "landingpage_about_p1", "landingpage_about_p2",
            "landingpage_about_stat1_val", "landingpage_about_stat1_label",
            "landingpage_about_stat2_val", "landingpage_about_stat2_label",
            "landingpage_about_image",
            "landingpage_trust_heading", "landingpage_trust_btn_corporate", "landingpage_trust_btn_personal",
            "landingpage_trust_client_heading",
            "landingpage_contact_h2", "landingpage_contact_p",
            "landingpage_contact_email", "landingpage_contact_phone", "landingpage_contact_address",
            "landingpage_contact_instagram", "landingpage_contact_linkedin", "landingpage_contact_maps_url",
            "landingpage_contact_hours_weekday", "landingpage_contact_hours_weekend",
            "landingpage_contact_copyright",
            "landingpage_whatsapp_phone", "landingpage_whatsapp_message", "landingpage_whatsapp_label",
            "landingpage_portfolio_h2", "landingpage_portfolio_download_text",
        );

        $data = array();
        foreach ($keys as $key) {
            $data[$key] = get_setting($key);
        }

        // Convert about image to full URL
        if (!empty($data['landingpage_about_image'])) {
            $data['landingpage_about_image'] = Landingpage_cms::get_upload_url($data['landingpage_about_image'], 'about');
        }

        // --- Hero slides from database ---
        $hero_model = model('HaviaCMS\Models\Lp_hero_model');
        $hero_slides = $hero_model->get_all_active();
        $data['hero_slides'] = array_map(function($slide) {
            return [
                'id' => (int)$slide->id,
                'image' => Landingpage_cms::get_upload_url($slide->image, 'hero'),
                'heading_h1' => $slide->heading_h1,
                'heading_h2' => $slide->heading_h2,
                'sort_order' => (int)$slide->sort_order,
            ];
        }, $hero_slides);

        // --- Project categories ---
        $cat_model = model('HaviaCMS\Models\Lp_category_model');
        $categories = $cat_model->get_all_active();
        $data['project_categories'] = array_map(function($cat) {
            return [
                'id' => (int)$cat->id,
                'name' => $cat->name,
            ];
        }, $categories);

        // --- Projects with images (Curated for "All" Tab logic) ---
        $proj_model = model('HaviaCMS\Models\Lp_project_model');
        $cat_model = model('HaviaCMS\Models\Lp_category_model');
        $img_model = model('HaviaCMS\Models\Lp_project_image_model');

        $categories = $cat_model->get_all_active();
        
        // Fetch all non-deleted projects ordered by created_at DESC
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        $all_projects_raw = $db->table($prefix . 'lp_projects')
            ->where('deleted', 0)
            ->orderBy('created_at', 'DESC')
            ->get()->getResult();

        // Group projects by category and count total per category for distribution logic
        $grouped_projects = [];
        $category_total_counts = []; 
        foreach ($all_projects_raw as $p) {
            $grouped_projects[$p->category_id][] = $p;
            $category_total_counts[$p->category_id] = ($category_total_counts[$p->category_id] ?? 0) + 1;
        }

        $selected_project_objs = [];
        $active_cat_ids = [];
        foreach ($categories as $cat) {
            if (!empty($grouped_projects[$cat->id])) {
                $active_cat_ids[] = $cat->id;
            }
        }

        // Phase 1: Ensure 1 latest representative per category (up to 9 total)
        $cat_to_process = array_slice($active_cat_ids, 0, 9);
        foreach ($cat_to_process as $cat_id) {
            $selected_project_objs[] = array_shift($grouped_projects[$cat_id]);
        }

        // Phase 2: Fill remaining slots up to 9
        if (count($selected_project_objs) < 9) {
            // Priority for extra slots: Categories with the most total projects in DB
            usort($cat_to_process, function($a, $b) use ($category_total_counts) {
                return $category_total_counts[$b] <=> $category_total_counts[$a];
            });

            while (count($selected_project_objs) < 9) {
                $added_in_round = false;
                foreach ($cat_to_process as $cat_id) {
                    if (count($selected_project_objs) >= 9) break;
                    if (!empty($grouped_projects[$cat_id])) {
                        $selected_project_objs[] = array_shift($grouped_projects[$cat_id]);
                        $added_in_round = true;
                    }
                }
                if (!$added_in_round) break;
            }
        }

        // Ensure final list is sorted by created_at DESC
        usort($selected_project_objs, function($a, $b) {
            return strtotime($b->created_at) <=> strtotime($a->created_at);
        });

        // Transform the selected 9 projects for API response
        $data['projects'] = array_map(function($p) use ($cat_model, $img_model) {
            $category = $cat_model->get_one($p->category_id);
            $category_name = ($category && $category->id) ? $category->name : '';

            $p_images = $img_model->get_by_project($p->id);
            $images = [];
            foreach ($p_images as $img) {
                $images[] = Landingpage_cms::get_upload_url($img->image_path, 'projects');
            }

            $scope = $p->scope;
            if (is_string($scope)) {
                $scope = array_map('trim', explode(',', $scope));
            }

            return [
                'id' => (int)$p->id,
                'title' => $p->title,
                'category' => $category_name,
                'category_id' => (int)$p->category_id,
                'location' => $p->location,
                'year' => $p->year,
                'client' => $p->client,
                'scope' => $scope ?: [],
                'story' => $p->description,
                'image' => !empty($images) ? $images[0] : '',
                'images' => $images,
                'created_at' => $p->created_at
            ];
        }, $selected_project_objs);

        // --- Team members ---
        $team_model = model('HaviaCMS\Models\Lp_team_model');
        $team = $team_model->get_all_active();
        $data['team_members'] = array_map(function($m) {
            return [
                'id' => (int)$m->id,
                'name' => $m->name,
                'role' => $m->job_title,
                'description' => isset($m->description) ? $m->description : '',
                'image' => Landingpage_cms::get_upload_url($m->image, 'team'),
            ];
        }, $team);

        // --- Gallery images ---
        $gallery_model = model('HaviaCMS\Models\Lp_gallery_model');
        $gallery = $gallery_model->get_all_active();
        $data['gallery_images'] = array_map(function($g) {
            return [
                'id' => (int)$g->id,
                'src' => Landingpage_cms::get_upload_url($g->image, 'gallery'),
                'description' => isset($g->description) ? $g->description : '',
            ];
        }, $gallery);

        // --- Testimonials ---
        $testimonial_model = model('HaviaCMS\Models\Lp_testimonial_model');
        $testimonials = $testimonial_model->get_all_active();
        $data['testimonials'] = array_map(function($t) {
            return [
                'id' => (int)$t->id,
                'type' => $t->type,
                'image' => Landingpage_cms::get_upload_url($t->image, 'testimonials'),
                'name' => $t->name,
                'role' => $t->subtitle,
                'quote' => $t->description,
            ];
        }, $testimonials);

        // --- Client logos ---
        $client_model = model('HaviaCMS\Models\Lp_client_model');
        $clients = $client_model->get_all_active();
        $data['client_logos'] = array_map(function($c) {
            return [
                'id' => (int)$c->id,
                'image' => Landingpage_cms::get_upload_url($c->image, 'clients'),
                'name' => $c->name,
            ];
        }, $clients);

        $data['test_version'] = 'v1.4-with-description';

        return $this->response->setJSON([
            "success" => true,
            "data" => $data
        ]);
    }

    /**
     * POST /api/haviacms/landingpage/request
     * Submit portfolio download request from landing page
     */
    function submit_request() {
        if ($this->request->getMethod() === "options") {
            return $this->response->setJSON(["status" => "ok"]);
        }

        $json = $this->request->getJSON(true);
        if (!$json) {
            $json = $this->request->getPost();
        }

        $name = $json['name'] ?? '';
        $contact = $json['contact'] ?? '';
        $interest = $json['interest'] ?? '';

        if (empty($name) || empty($contact)) {
            return $this->response->setJSON([
                "success" => false,
                "message" => "Name and contact are required."
            ]);
        }

        // Detect contact type
        $contact_type = 'unknown';
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $contact_type = 'email';
        } elseif (preg_match('/^[\+]?[0-9\s\-]{8,}$/', preg_replace('/[\s\-]/', '', $contact))) {
            $contact_type = 'whatsapp';
        }

        $model = model('HaviaCMS\Models\Lp_request_model');
        $data = [
            'name' => $name,
            'contact' => $contact,
            'contact_type' => $contact_type,
            'interest' => $interest,
            'status' => 'pending',
        ];

        $save_id = $model->ci_save($data);
        if ($save_id) {
            return $this->response->setJSON([
                "success" => true,
                "message" => "Your request has been submitted. We'll get back to you within 1-2 business days."
            ]);
        } else {
            return $this->response->setJSON([
                "success" => false,
                "message" => "Something went wrong. Please try again."
            ]);
        }
    }
}
