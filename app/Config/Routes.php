<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Route default
$routes->get('/', 'Signin::index');
$routes->setDefaultController('Signin');

// 1. Load Plugin Routes (Ini bagian yang Anda cari)
$_activated_plugins_json = APPPATH . 'Config/activated_plugins.json';
if (is_file($_activated_plugins_json)) {
    $_activated_plugins = @json_decode(file_get_contents($_activated_plugins_json));
    if ($_activated_plugins && is_array($_activated_plugins)) {
        foreach ($_activated_plugins as $_plugin_name) {
            $_plugin_route_file = ROOTPATH . 'plugins/' . $_plugin_name . '/Config/Routes.php';
            if (is_file($_plugin_route_file)) {
                require $_plugin_route_file;
            }
        }
    }
}

// 2. Auto-routing untuk Controller Utama
if ($dh = opendir(APPPATH . "Controllers")) {
    $excluded_controllers = array("App_Controller", "Security_Controller");
    while (($file = readdir($dh)) !== false) {
        if ($file != "." && $file != ".." && $file != "index.html") {
            $controller = str_replace(".php", "", $file);
            if (!in_array($controller, $excluded_controllers)) {
                $routes->get(strtolower($controller), "$controller::index");
                $routes->get(strtolower($controller) . '/(:any)', "$controller::$1");
                $routes->post(strtolower($controller) . '/(:any)', "$controller::$1");
            }
        }
    }
    closedir($dh);
}

// 3. Load Environment Routes jika ada
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}