<?php

namespace HaviaCMS\Controllers;

use App\Controllers\Security_Controller;

class Landingpage_cms extends Security_Controller {

    private $upload_base;

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
        $this->upload_base = FCPATH . 'files' . DIRECTORY_SEPARATOR . 'lp_uploads' . DIRECTORY_SEPARATOR;
        // Ensure landing page tables exist (safe, runs only once per request)
        havia_create_lp_tables();
    }

    // ============================================================
    // TAB VIEWS
    // ============================================================

    function index() {
        return $this->template->rander('HaviaCMS\Views\index');
    }

    function hero() {
        $model = model('HaviaCMS\Models\Lp_hero_model');
        $data['slides'] = $model->get_all_active();
        return $this->template->view('HaviaCMS\Views\tabs\hero', $data);
    }

    function about() {
        return $this->template->view('HaviaCMS\Views\tabs\about');
    }

    function team() {
        $model = model('HaviaCMS\Models\Lp_team_model');
        $data['members'] = $model->get_all_active();
        return $this->template->view('HaviaCMS\Views\tabs\team', $data);
    }

    function gallery() {
        $model = model('HaviaCMS\Models\Lp_gallery_model');
        $data['images'] = $model->get_all_active();
        return $this->template->view('HaviaCMS\Views\tabs\gallery', $data);
    }

    function portfolio() {
        $cat_model = model('HaviaCMS\Models\Lp_category_model');
        $proj_model = model('HaviaCMS\Models\Lp_project_model');
        $data['categories'] = $cat_model->get_all_active();
        $data['projects'] = $proj_model->get_all_with_images();
        return $this->template->view('HaviaCMS\Views\tabs\portfolio', $data);
    }

    function trust() {
        $t_model = model('HaviaCMS\Models\Lp_testimonial_model');
        $c_model = model('HaviaCMS\Models\Lp_client_model');
        $data['corporate_testimonials'] = $t_model->get_by_type('corporate');
        $data['personal_testimonials'] = $t_model->get_by_type('personal');
        $data['client_logos'] = $c_model->get_all_active();
        return $this->template->view('HaviaCMS\Views\tabs\trust', $data);
    }

    function contact() {
        return $this->template->view('HaviaCMS\Views\tabs\contact');
    }

    function whatsapp() {
        return $this->template->view('HaviaCMS\Views\tabs\whatsapp');
    }

    function requests() {
        $model = model('HaviaCMS\Models\Lp_request_model');
        $data['requests'] = $model->get_all_active();
        return $this->template->view('HaviaCMS\Views\tabs\requests', $data);
    }

    // ============================================================
    // MODAL FORMS
    // ============================================================

    function hero_modal() {
        $id = $this->request->getPost('id');
        $data['model_info'] = (object)['id' => '', 'image' => '', 'heading_h1' => '', 'heading_h2' => '', 'sort_order' => 0];
        if ($id) {
            $model = model('HaviaCMS\Models\Lp_hero_model');
            $info = $model->get_one($id);
            if ($info && $info->id) $data['model_info'] = $info;
        }
        return $this->template->view('HaviaCMS\Views\modals\hero_modal', $data);
    }

    function project_modal() {
        $id = $this->request->getPost('id');
        $cat_model = model('HaviaCMS\Models\Lp_category_model');
        $data['categories'] = $cat_model->get_all_active();
        $data['model_info'] = (object)['id' => '', 'category_id' => '', 'title' => '', 'location' => '', 'year' => '', 'client' => '', 'scope' => '', 'description' => ''];
        $data['project_images'] = [];
        if ($id) {
            $model = model('HaviaCMS\Models\Lp_project_model');
            $info = $model->get_with_images($id);
            if ($info && $info->id) {
                $data['model_info'] = $info;
                $data['project_images'] = $info->project_images ?? [];
            }
        }
        return $this->template->view('HaviaCMS\Views\modals\project_modal', $data);
    }

    function team_modal() {
        $id = $this->request->getPost('id');
        $data['model_info'] = (object)['id' => '', 'name' => '', 'job_title' => '', 'image' => '', 'sort_order' => 0];
        if ($id) {
            $model = model('HaviaCMS\Models\Lp_team_model');
            $info = $model->get_one($id);
            if ($info && $info->id) $data['model_info'] = $info;
        }
        return $this->template->view('HaviaCMS\Views\modals\team_modal', $data);
    }

    function testimonial_modal() {
        $id = $this->request->getPost('id');
        $type = $this->request->getPost('type') ?: 'corporate';
        $data['model_info'] = (object)['id' => '', 'type' => $type, 'image' => '', 'name' => '', 'subtitle' => '', 'description' => '', 'sort_order' => 0];
        if ($id) {
            $model = model('HaviaCMS\Models\Lp_testimonial_model');
            $info = $model->get_one($id);
            if ($info && $info->id) $data['model_info'] = $info;
        }
        return $this->template->view('HaviaCMS\Views\modals\testimonial_modal', $data);
    }

    // ============================================================
    // SAVE TEXT SETTINGS (existing approach, kept for simple fields)
    // ============================================================

    function save_settings() {
        $settings = array(
            // Hero text
            "landingpage_hero_label", "landingpage_hero_btn1", "landingpage_hero_btn2",
            // About
            "landingpage_about_accent", "landingpage_about_h2", "landingpage_about_p1", "landingpage_about_p2",
            "landingpage_about_stat1_val", "landingpage_about_stat1_label",
            "landingpage_about_stat2_val", "landingpage_about_stat2_label",
            // Trust headings
            "landingpage_trust_heading", "landingpage_trust_btn_corporate", "landingpage_trust_btn_personal",
            "landingpage_trust_client_heading",
            // Contact / Footer
            "landingpage_contact_h2", "landingpage_contact_p",
            "landingpage_contact_email", "landingpage_contact_phone", "landingpage_contact_address",
            "landingpage_contact_instagram", "landingpage_contact_linkedin", "landingpage_contact_maps_url",
            "landingpage_contact_hours_weekday", "landingpage_contact_hours_weekend",
            "landingpage_contact_copyright",
            // WhatsApp
            "landingpage_whatsapp_phone", "landingpage_whatsapp_message", "landingpage_whatsapp_label",
            // Portfolio text
            "landingpage_portfolio_h2", "landingpage_portfolio_download_text",
        );

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) continue;
            $this->Settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    // ============================================================
    // HERO SLIDES CRUD
    // ============================================================

    function save_hero_slide() {
        $model = model('HaviaCMS\Models\Lp_hero_model');
        $id = $this->request->getPost('id');

        // Check max 5
        if (!$id && $model->get_active_count() >= 5) {
            echo json_encode(array("success" => false, "message" => "Maximum 5 hero slides allowed."));
            return;
        }

        $data = [
            'heading_h1' => $this->request->getPost('heading_h1'),
            'heading_h2' => $this->request->getPost('heading_h2'),
            'sort_order' => $this->request->getPost('sort_order') ?: 0,
        ];

        try {
            $image = $this->_handle_upload('image', 'hero');
            if ($image) $data['image'] = $image;
        } catch (\Exception $e) {
            echo json_encode(array("success" => false, "message" => $e->getMessage()));
            return;
        }

        $save_id = $model->ci_save($data, $id ?: 0);
        if ($save_id) {
            echo json_encode(array("success" => true, "message" => "Hero slide saved.", "id" => $save_id));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to save."));
        }
    }

    function delete_hero_slide() {
        $id = $this->request->getPost('id');
        $model = model('HaviaCMS\Models\Lp_hero_model');
        if ($model->delete($id)) {
            echo json_encode(array("success" => true, "message" => "Slide deleted."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to delete."));
        }
    }

    // ============================================================
    // ABOUT IMAGE
    // ============================================================

    function save_about_image() {
        $image = $this->_handle_upload('about_image', 'about');
        if ($image) {
            $this->Settings_model->save_setting('landingpage_about_image', $image);
            echo json_encode(array("success" => true, "message" => "About image saved."));
        } else {
            echo json_encode(array("success" => false, "message" => "No image uploaded or upload failed."));
        }
    }

    // ============================================================
    // TEAM MEMBERS CRUD
    // ============================================================

    function save_team_member() {
        $model = model('HaviaCMS\Models\Lp_team_model');
        $id = $this->request->getPost('id');

        $data = [
            'name' => $this->request->getPost('name'),
            'job_title' => $this->request->getPost('job_title'),
            'sort_order' => $this->request->getPost('sort_order') ?: 0,
        ];

        $image = $this->_handle_upload('image', 'team');
        if ($image) $data['image'] = $image;

        $save_id = $model->ci_save($data, $id ?: 0);
        if ($save_id) {
            echo json_encode(array("success" => true, "message" => "Team member saved.", "id" => $save_id));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to save."));
        }
    }

    function delete_team_member() {
        $id = $this->request->getPost('id');
        $model = model('HaviaCMS\Models\Lp_team_model');
        if ($model->delete($id)) {
            echo json_encode(array("success" => true, "message" => "Team member deleted."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to delete."));
        }
    }

    // ============================================================
    // GALLERY CRUD
    // ============================================================

    function save_gallery_image() {
        $model = model('HaviaCMS\Models\Lp_gallery_model');
        $id = $this->request->getPost('id');

        if (!$id && $model->get_active_count() >= 12) {
            echo json_encode(array("success" => false, "message" => "Maximum 12 gallery images allowed."));
            return;
        }

        $data = ['sort_order' => $this->request->getPost('sort_order') ?: 0];
        $image = $this->_handle_upload('image', 'gallery');
        if ($image) $data['image'] = $image;

        $save_id = $model->ci_save($data, $id ?: 0);
        if ($save_id) {
            echo json_encode(array("success" => true, "message" => "Gallery image saved.", "id" => $save_id));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to save."));
        }
    }

    function delete_gallery_image() {
        $id = $this->request->getPost('id');
        $model = model('HaviaCMS\Models\Lp_gallery_model');
        if ($model->delete($id)) {
            echo json_encode(array("success" => true, "message" => "Gallery image deleted."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to delete."));
        }
    }

    // ============================================================
    // PROJECT CATEGORIES CRUD
    // ============================================================

    function save_category() {
        $model = model('HaviaCMS\Models\Lp_category_model');
        $id = $this->request->getPost('id');
        $data = [
            'name' => $this->request->getPost('name'),
            'sort_order' => $this->request->getPost('sort_order') ?: 0,
        ];
        $save_id = $model->ci_save($data, $id ?: 0);
        if ($save_id) {
            echo json_encode(array("success" => true, "message" => "Category saved.", "id" => $save_id));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to save."));
        }
    }

    function delete_category() {
        $id = $this->request->getPost('id');
        $cat_model = model('HaviaCMS\Models\Lp_category_model');
        $proj_model = model('HaviaCMS\Models\Lp_project_model');

        // Check if category has projects
        $projects = $proj_model->get_by_category($id);
        if (count($projects) > 0) {
            echo json_encode(array("success" => false, "message" => "Cannot delete category that has projects. Remove projects first."));
            return;
        }

        if ($cat_model->delete($id)) {
            echo json_encode(array("success" => true, "message" => "Category deleted."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to delete."));
        }
    }

    // ============================================================
    // PROJECTS CRUD
    // ============================================================

    function save_project() {
        $model = model('HaviaCMS\Models\Lp_project_model');
        $img_model = model('HaviaCMS\Models\Lp_project_image_model');
        $id = $this->request->getPost('id');

        $data = [
            'category_id' => $this->request->getPost('category_id'),
            'title' => $this->request->getPost('title'),
            'location' => $this->request->getPost('location'),
            'year' => $this->request->getPost('year'),
            'client' => $this->request->getPost('client'),
            'scope' => $this->request->getPost('scope'),
            'description' => $this->request->getPost('description'),
            'sort_order' => $this->request->getPost('sort_order') ?: 0,
        ];

        $save_id = $model->ci_save($data, $id ?: 0);
        if (!$save_id) {
            echo json_encode(array("success" => false, "message" => "Failed to save project."));
            return;
        }

        // Handle up to 3 project images
        for ($i = 1; $i <= 3; $i++) {
            $image = $this->_handle_upload("project_image_$i", 'projects');
            if ($image) {
                $img_data = [
                    'project_id' => $save_id,
                    'image_path' => $image,
                    'sort_order' => $i,
                ];
                // Check if there's an existing image at this position to update
                $existing_id = $this->request->getPost("existing_image_id_$i");
                $img_model->ci_save($img_data, $existing_id ?: 0);
            }
        }

        echo json_encode(array("success" => true, "message" => "Project saved.", "id" => $save_id));
    }

    function delete_project() {
        $id = $this->request->getPost('id');
        $model = model('HaviaCMS\Models\Lp_project_model');
        $img_model = model('HaviaCMS\Models\Lp_project_image_model');

        $img_model->delete_by_project($id);
        if ($model->delete($id)) {
            echo json_encode(array("success" => true, "message" => "Project deleted."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to delete."));
        }
    }

    // ============================================================
    // TESTIMONIALS CRUD
    // ============================================================

    function save_testimonial() {
        $model = model('HaviaCMS\Models\Lp_testimonial_model');
        $id = $this->request->getPost('id');
        $type = $this->request->getPost('type') ?: 'corporate';

        // Max 5 per type
        if (!$id && $model->count_by_type($type) >= 5) {
            echo json_encode(array("success" => false, "message" => "Maximum 5 testimonials per type allowed."));
            return;
        }

        $data = [
            'type' => $type,
            'name' => $this->request->getPost('name'),
            'subtitle' => $this->request->getPost('subtitle'),
            'description' => $this->request->getPost('description'),
            'sort_order' => $this->request->getPost('sort_order') ?: 0,
        ];

        $image = $this->_handle_upload('image', 'testimonials');
        if ($image) $data['image'] = $image;

        $save_id = $model->ci_save($data, $id ?: 0);
        if ($save_id) {
            echo json_encode(array("success" => true, "message" => "Testimonial saved.", "id" => $save_id));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to save."));
        }
    }

    function delete_testimonial() {
        $id = $this->request->getPost('id');
        $model = model('HaviaCMS\Models\Lp_testimonial_model');
        if ($model->delete($id)) {
            echo json_encode(array("success" => true, "message" => "Testimonial deleted."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to delete."));
        }
    }

    // ============================================================
    // CLIENT LOGOS CRUD
    // ============================================================

    function save_client_logo() {
        $model = model('HaviaCMS\Models\Lp_client_model');
        $id = $this->request->getPost('id');

        if (!$id && $model->get_active_count() >= 50) {
            echo json_encode(array("success" => false, "message" => "Maximum 50 client logos allowed."));
            return;
        }

        $data = [
            'name' => $this->request->getPost('name') ?: '',
            'sort_order' => $this->request->getPost('sort_order') ?: 0,
        ];

        $image = $this->_handle_upload('image', 'clients');
        if ($image) $data['image'] = $image;

        $save_id = $model->ci_save($data, $id ?: 0);
        if ($save_id) {
            echo json_encode(array("success" => true, "message" => "Client logo saved.", "id" => $save_id));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to save."));
        }
    }

    function delete_client_logo() {
        $id = $this->request->getPost('id');
        $model = model('HaviaCMS\Models\Lp_client_model');
        if ($model->delete($id)) {
            echo json_encode(array("success" => true, "message" => "Client logo deleted."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to delete."));
        }
    }

    // ============================================================
    // PORTFOLIO REQUESTS
    // ============================================================

    function mark_request_sent() {
        $id = $this->request->getPost('id');
        $model = model('HaviaCMS\Models\Lp_request_model');
        $model->mark_sent($id);
        echo json_encode(array("success" => true, "message" => "Request marked as sent."));
    }

    function delete_request() {
        $id = $this->request->getPost('id');
        $model = model('HaviaCMS\Models\Lp_request_model');
        if ($model->delete($id)) {
            echo json_encode(array("success" => true, "message" => "Request deleted."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to delete."));
        }
    }

    // ============================================================
    // FILE UPLOAD HELPER
    // ============================================================

    private function _handle_upload($field_name, $subfolder) {
        $file = $this->request->getFile($field_name);
        
        // If no file was selected/uploaded, just return null
        if (!$file || $file->getError() == UPLOAD_ERR_NO_FILE) {
            return null;
        }

        // Validate Max Size (5MB) - Check early
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception("File is too large! Maximum allowed size is 5MB.");
        }

        if (!$file->hasMoved()) {
            $target_dir = $this->upload_base . $subfolder;
            if (!is_dir($target_dir)) {
                @mkdir($target_dir, 0775, true);
            }
            
            $newName = $file->getRandomName();
            
            try {
                if ($file->move($target_dir, $newName)) {
                    return $newName;
                }
            } catch (\Exception $e) {
                log_message('error', 'HaviaCMS upload failed: ' . $e->getMessage());
                throw new \Exception("Failed to save the image to the server: " . $e->getMessage());
            }
        }
        return null;
    }

    /**
     * Helper to get the public URL for an uploaded file
     */
    public static function get_upload_url($filename, $subfolder) {
        if (!$filename) return '';
        $base = rtrim(base_url(), '/');
        return $base . '/files/lp_uploads/' . $subfolder . '/' . $filename;
    }
}
