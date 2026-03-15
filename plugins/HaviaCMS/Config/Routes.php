<?php

$routes->group("landingpage_cms", ["namespace" => "HaviaCMS\Controllers"], function ($routes) {
    $routes->get("/", "Landingpage_cms::index");
    $routes->post("/", "Landingpage_cms::index");
    $routes->get("hero", "Landingpage_cms::hero");
    $routes->get("about", "Landingpage_cms::about");
    $routes->get("portfolio", "Landingpage_cms::portfolio");
    $routes->get("trust", "Landingpage_cms::trust");
    $routes->get("contact", "Landingpage_cms::contact");
    $routes->get("whatsapp", "Landingpage_cms::whatsapp");
    $routes->post("save_settings", "Landingpage_cms::save_settings");
});

$routes->group("user_management", ["namespace" => "HaviaCMS\Controllers"], function ($routes) {
    $routes->get("/", "User_management::index");
    $routes->post("list_data", "User_management::list_data");
    $routes->post("modal_form", "User_management::modal_form");
    $routes->post("save", "User_management::save");
    $routes->post("delete", "User_management::delete");
});


$routes->group("api", ["namespace" => "HaviaCMS\Controllers"], function ($routes) {
    $routes->post("login", "AuthController::login");
    $routes->post("register", "AuthController::register");
    
    $routes->group("haviacms", function ($routes) {
        $routes->get("events", "EventsApi::index");
        $routes->get("events/labels", "EventsApi::labels");
        
        // Landing Page API
        $routes->get("landingpage/settings", "Landingpage_api::settings");
        $routes->options("landingpage/settings", "Landingpage_api::settings");
        
        // Attendance Routes
        $routes->get("attendance", "AttendanceApi::index");
        $routes->options("attendance", "AttendanceApi::index");
        $routes->get("attendance/debug", "AttendanceApi::debug");
        $routes->post("attendance", "AttendanceApi::create");
        $routes->options("attendance/(:any)", "AttendanceApi::index");
        $routes->put("attendance/(:num)", "AttendanceApi::update/$1");
        $routes->delete("attendance/(:num)", "AttendanceApi::delete/$1");

        // Projects & Tasks Optimized Routes
        $routes->get("projects", "ProjectsApi::index");
        $routes->get("tasks", "TasksApi::index");

        // Profile Management
        $routes->put("profile/update", "ProfileApi::update_profile");
        $routes->get("profile/verify_status", "ProfileApi::verify_status");
        $routes->post("profile/upload_avatar", "ProfileApi::upload_avatar");
        $routes->post("profile/delete_avatar", "ProfileApi::delete_avatar");
        $routes->post("profile/reset_password", "ProfileApi::reset_password");
    });
});
