<?php

// ============================================================
// LANDING PAGE CMS
// ============================================================
$routes->group("landingpage_cms", ["namespace" => "HaviaCMS\Controllers"], function ($routes) {
    $routes->get("/", "Landingpage_cms::index");
    $routes->post("/", "Landingpage_cms::index");

    // Tab views
    $routes->get("hero", "Landingpage_cms::hero");
    $routes->get("about", "Landingpage_cms::about");
    $routes->get("team", "Landingpage_cms::team");
    $routes->get("gallery", "Landingpage_cms::gallery");
    $routes->get("portfolio", "Landingpage_cms::portfolio");
    $routes->get("trust", "Landingpage_cms::trust");
    $routes->get("contact", "Landingpage_cms::contact");
    $routes->get("whatsapp", "Landingpage_cms::whatsapp");
    $routes->get("requests", "Landingpage_cms::requests");

    // Modal forms
    $routes->post("hero_modal", "Landingpage_cms::hero_modal");
    $routes->post("project_modal", "Landingpage_cms::project_modal");
    $routes->post("team_modal", "Landingpage_cms::team_modal");
    $routes->post("testimonial_modal", "Landingpage_cms::testimonial_modal");

    // Save endpoints (text settings)
    $routes->post("save_settings", "Landingpage_cms::save_settings");

    // Hero CRUD
    $routes->post("save_hero_slide", "Landingpage_cms::save_hero_slide");
    $routes->post("delete_hero_slide", "Landingpage_cms::delete_hero_slide");

    // About image
    $routes->post("save_about_image", "Landingpage_cms::save_about_image");

    // Team CRUD
    $routes->post("save_team_member", "Landingpage_cms::save_team_member");
    $routes->post("delete_team_member", "Landingpage_cms::delete_team_member");

    // Gallery CRUD
    $routes->post("save_gallery_image", "Landingpage_cms::save_gallery_image");
    $routes->post("delete_gallery_image", "Landingpage_cms::delete_gallery_image");

    // Category CRUD
    $routes->post("save_category", "Landingpage_cms::save_category");
    $routes->post("delete_category", "Landingpage_cms::delete_category");

    // Project CRUD
    $routes->post("save_project", "Landingpage_cms::save_project");
    $routes->post("delete_project", "Landingpage_cms::delete_project");

    // Testimonial CRUD
    $routes->post("save_testimonial", "Landingpage_cms::save_testimonial");
    $routes->post("delete_testimonial", "Landingpage_cms::delete_testimonial");

    // Client Logo CRUD
    $routes->post("save_client_logo", "Landingpage_cms::save_client_logo");
    $routes->post("delete_client_logo", "Landingpage_cms::delete_client_logo");

    // Portfolio Requests
    $routes->post("mark_request_sent", "Landingpage_cms::mark_request_sent");
    $routes->post("delete_request", "Landingpage_cms::delete_request");
});

// ============================================================
// USER MANAGEMENT
// ============================================================
$routes->group("user_management", ["namespace" => "HaviaCMS\Controllers"], function ($routes) {
    $routes->get("/", "User_management::index");
    $routes->post("list_data", "User_management::list_data");
    $routes->post("modal_form", "User_management::modal_form");
    $routes->post("save", "User_management::save");
    $routes->post("delete", "User_management::delete");
});

// ============================================================
// PUBLIC API
// ============================================================
$routes->group("api", ["namespace" => "HaviaCMS\Controllers"], function ($routes) {
    $routes->post("login", "AuthController::login");
    $routes->post("register", "AuthController::register");
    
    $routes->group("haviacms", function ($routes) {
        $routes->get("events", "EventsApi::index");
        $routes->get("events/labels", "EventsApi::labels");
        
        // Landing Page API
        $routes->get("landingpage/settings", "Landingpage_api::settings");
        $routes->options("landingpage/settings", "Landingpage_api::settings");
        $routes->post("landingpage/request", "Landingpage_api::submit_request");
        $routes->options("landingpage/request", "Landingpage_api::submit_request");
        
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

        // Leave Management
        $routes->get("leaves", "LeavesApi::index");
        $routes->post("leaves", "LeavesApi::create");
        $routes->get("leave_types", "LeavesApi::leave_types");

        // Finance
        $routes->get("finance/summary", "FinanceApi::summary");
        $routes->get("finance/salaries", "FinanceApi::salaries");

        // Notifications
        $routes->get("notifications", "NotificationsApi::index");
    });
});
