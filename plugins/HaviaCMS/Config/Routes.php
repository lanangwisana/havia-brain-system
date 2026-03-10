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

$routes->group("landingpage_api", ["namespace" => "HaviaCMS\Controllers"], function ($routes) {
    $routes->get("settings", "Landingpage_api::settings");
    $routes->options("settings", "Landingpage_api::settings");
});

$routes->group("api", ["namespace" => "HaviaCMS\Controllers"], function ($routes) {
    $routes->post("login", "AuthController::login");
    $routes->post("register", "AuthController::register");
});


