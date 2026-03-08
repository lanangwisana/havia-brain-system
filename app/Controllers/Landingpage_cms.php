<?php

namespace App\Controllers;

class Landingpage_cms extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
    }

    function index() {
        return $this->template->rander("landingpage_cms/index");
    }

    function hero() {
        return $this->template->view("landingpage_cms/tabs/hero");
    }

    function about() {
        return $this->template->view("landingpage_cms/tabs/about");
    }

    function portfolio() {
        return $this->template->view("landingpage_cms/tabs/portfolio");
    }

    function trust() {
        return $this->template->view("landingpage_cms/tabs/trust");
    }

    function contact() {
        return $this->template->view("landingpage_cms/tabs/contact");
    }

    function whatsapp() {
        return $this->template->view("landingpage_cms/tabs/whatsapp");
    }

    function save_settings() {
        $settings = array(
            "landingpage_hero_label", "landingpage_hero_h1", "landingpage_hero_h2", "landingpage_hero_p",
            "landingpage_about_accent", "landingpage_about_h2", "landingpage_about_p1", "landingpage_about_p2",
            "landingpage_about_stat1_val", "landingpage_about_stat1_label",
            "landingpage_about_stat2_val", "landingpage_about_stat2_label",
            "landingpage_portfolio_accent", "landingpage_portfolio_h2", "landingpage_portfolio_categories", "landingpage_portfolio_json",
            "landingpage_trust_h2", "landingpage_trust_p",
            "landingpage_contact_h2", "landingpage_contact_p", "landingpage_contact_email", "landingpage_contact_phone", "landingpage_contact_address",
            "landingpage_whatsapp_phone", "landingpage_whatsapp_message"
        );

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                // $value = "";
                continue; // only save what's passed in the form
            }

            $this->Settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }
}
