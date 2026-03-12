<?php

namespace HaviaCMS\Controllers;

use App\Controllers\Security_Controller;

class Landingpage_cms extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
    }

    function index() {
        return $this->template->rander('HaviaCMS\Views\index');
    }

    function hero() {
        return $this->template->view('HaviaCMS\Views\tabs\hero');
    }

    function about() {
        return $this->template->view('HaviaCMS\Views\tabs\about');
    }

    function portfolio() {
        return $this->template->view('HaviaCMS\Views\tabs\portfolio');
    }

    function trust() {
        return $this->template->view('HaviaCMS\Views\tabs\trust');
    }

    function contact() {
        return $this->template->view('HaviaCMS\Views\tabs\contact');
    }

    function whatsapp() {
        return $this->template->view('HaviaCMS\Views\tabs\whatsapp');
    }

    function save_settings() {
        $settings = array(
            // Hero
            "landingpage_hero_label", "landingpage_hero_h1", "landingpage_hero_h2", "landingpage_hero_h3", "landingpage_hero_p",
            "landingpage_hero_btn1", "landingpage_hero_btn2",
            // About
            "landingpage_about_accent", "landingpage_about_h2", "landingpage_about_p1", "landingpage_about_p2",
            "landingpage_about_stat1_val", "landingpage_about_stat1_label",
            "landingpage_about_stat2_val", "landingpage_about_stat2_label",
            // Portfolio
            "landingpage_portfolio_accent", "landingpage_portfolio_h2", "landingpage_portfolio_categories",
            "landingpage_portfolio_json", "landingpage_portfolio_download_text",
            // Trust / Testimonial
            "landingpage_trust_accent", "landingpage_trust_h2", "landingpage_trust_p",
            "landingpage_trust_testimonials_json", "landingpage_trust_client_heading",
            "landingpage_trust_clients_json", "landingpage_trust_footer_text",
            // Contact / Footer
            "landingpage_contact_h2", "landingpage_contact_p",
            "landingpage_contact_email", "landingpage_contact_phone", "landingpage_contact_address",
            "landingpage_contact_instagram", "landingpage_contact_linkedin", "landingpage_contact_maps_url",
            "landingpage_contact_hours_weekday", "landingpage_contact_hours_weekend",
            "landingpage_contact_copyright",
            // WhatsApp
            "landingpage_whatsapp_phone", "landingpage_whatsapp_message", "landingpage_whatsapp_label"
        );

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                continue;
            }

            $this->Settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }
}
