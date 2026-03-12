<?php

namespace HaviaCMS\Controllers;

use App\Controllers\App_Controller;

class Landingpage_api extends App_Controller {

    function __construct() {
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
    }

    function settings() {
        if ($this->request->getMethod() === "options") {
            return $this->response->setJSON(["status" => "ok"]);
        }

        $keys = array(
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

        $data = array();
        foreach ($keys as $key) {
            $data[$key] = get_setting($key);
        }

        // Parse portfolio JSON
        if (isset($data['landingpage_portfolio_json']) && $data['landingpage_portfolio_json']) {
            $decoded = json_decode($data['landingpage_portfolio_json']);
            if ($decoded !== null) {
                $data['landingpage_portfolio_json'] = $decoded;
            }
        }

        // Parse testimonials JSON
        if (isset($data['landingpage_trust_testimonials_json']) && $data['landingpage_trust_testimonials_json']) {
            $decoded = json_decode($data['landingpage_trust_testimonials_json']);
            if ($decoded !== null) {
                $data['landingpage_trust_testimonials_json'] = $decoded;
            }
        }

        // Parse clients JSON
        if (isset($data['landingpage_trust_clients_json']) && $data['landingpage_trust_clients_json']) {
            $decoded = json_decode($data['landingpage_trust_clients_json']);
            if ($decoded !== null) {
                $data['landingpage_trust_clients_json'] = $decoded;
            }
        }

        return $this->response->setJSON([
            "success" => true,
            "data" => $data
        ]);
    }
}
