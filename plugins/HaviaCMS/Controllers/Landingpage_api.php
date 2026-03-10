<?php

namespace HaviaCMS\Controllers;

use App\Controllers\App_Controller;

class Landingpage_api extends App_Controller {

    /**
     * @var \CodeIgniter\HTTP\IncomingRequest
     */
    protected $request;


    public function __construct() {
        parent::__construct();
        // Allow CORS for Next.js frontend
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
    }

    public function settings() {
        if ($this->request->getMethod() === "options") {
            return $this->response->setJSON(["status" => "ok"]);
        }

        $keys = array(
            "landingpage_hero_label", "landingpage_hero_h1", "landingpage_hero_h2", "landingpage_hero_p",
            "landingpage_about_accent", "landingpage_about_h2", "landingpage_about_p1", "landingpage_about_p2",
            "landingpage_about_stat1_val", "landingpage_about_stat1_label",
            "landingpage_about_stat2_val", "landingpage_about_stat2_label",
            "landingpage_portfolio_accent", "landingpage_portfolio_h2", "landingpage_portfolio_categories", "landingpage_portfolio_json",
            "landingpage_trust_h2", "landingpage_trust_p",
            "landingpage_contact_h2", "landingpage_contact_p", "landingpage_contact_email", "landingpage_contact_phone", "landingpage_contact_address",
            "landingpage_whatsapp_phone", "landingpage_whatsapp_message"
        );

        $data = [];
        foreach ($keys as $key) {
            $data[$key] = get_setting($key);
        }

        // Parse portfolio JSON if exists, else return empty array
        if (!empty($data['landingpage_portfolio_json'])) {
            $data['landingpage_portfolio_json'] = json_decode($data['landingpage_portfolio_json'], true);
        } else {
            // Default placeholder if empty
            $data['landingpage_portfolio_json'] = [
                ["title" => "Eunoia Aesthetic Clinic", "subtitle" => "Eunoia Clinic", "category" => "Commercial", "img" => "/havia-project-1.jpg"],
                ["title" => "Raya Office Tower", "subtitle" => "Raya Office", "category" => "Corporate", "img" => "/havia-project-2.jpg"],
                ["title" => "Casa de Rosa", "subtitle" => "Private House", "category" => "Residential", "img" => "/havia-project-3.jpg"]
            ];
        }

        return $this->response->setJSON([
            "success" => true,
            "data" => $data
        ]);
    }
}
