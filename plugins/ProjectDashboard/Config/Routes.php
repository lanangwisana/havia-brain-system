<?php

// ProjectDashboard Plugin Routes
$routes->group('project_dashboard', ['namespace' => 'ProjectDashboard\Controllers'], function ($routes) {
    $routes->get('/', 'Project_dashboard::index');
    $routes->get('index', 'Project_dashboard::index');
    $routes->get('view/(:any)', 'Project_dashboard::view/$1');
    $routes->post('delete_weight', 'Project_dashboard::delete_weight');
    $routes->get('modal_edit_rab', 'Project_dashboard::modal_edit_rab');
    $routes->post('modal_edit_rab', 'Project_dashboard::modal_edit_rab');
    $routes->post('save_rab_weight', 'Project_dashboard::save_rab_weight');
    $routes->get('modal_edit_parent_dates', 'Project_dashboard::modal_edit_parent_dates');
    $routes->post('modal_edit_parent_dates', 'Project_dashboard::modal_edit_parent_dates');
    $routes->post('save_parent_dates', 'Project_dashboard::save_parent_dates');
    $routes->post('approve_rab', 'Project_dashboard::approve_rab');
    $routes->post('reject_rab', 'Project_dashboard::reject_rab');
});
