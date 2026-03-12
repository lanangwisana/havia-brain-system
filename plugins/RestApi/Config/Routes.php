<?php

namespace Config;

$routes = Services::routes();

$rest_api_namespace = ['namespace' => 'RestApi\Controllers'];

$routes->get('api_settings', 'Api_settings_Controller::index', $rest_api_namespace);

//for loading datatable
$routes->post('restapi/table', 'Api_settings_Controller::table', $rest_api_namespace);

//for show modal
$routes->post('restapi/modal/?(:num)?', 'Api_settings_Controller::modal_form/$1', $rest_api_namespace);

//for Add/Edit Api Users
$routes->post('restapi/manage/', 'Api_settings_Controller::save', $rest_api_namespace);

//for delete Api Users
$routes->post('restapi/remove/(:num)', 'Api_settings_Controller::delete_user/$1', $rest_api_namespace);

//For all kind of api get request
$routes->group('api', $rest_api_namespace, function ($routes) {
	$routes->add('client_groups', 'UtilitiesController::getClientGroups');
	$routes->add('project_labels', 'UtilitiesController::getProejctLabels');
	$routes->add('invoice_labels', 'UtilitiesController::getInvoiceLabels');
	$routes->add('ticket_labels', 'UtilitiesController::getTicketLabels');
	$routes->add('invoice_tax', 'UtilitiesController::getInvoiceTaxes');
	$routes->add('contact_by_clientid/(:num)', 'UtilitiesController::getContactByClientid/$1');
	$routes->add('ticket_type', 'UtilitiesController::getTicketType');
	$routes->add('staff_owner', 'UtilitiesController::getStaffOwner');
	$routes->add('project_members', 'UtilitiesController::getProjectMembers');
});

$routes->group('api', $rest_api_namespace, function ($routes) {
	$routes->get('leads', 'LeadsController::index'); //get
	$routes->get('leads/(:segment)', 'LeadsController::show/$1'); //get by id
	$routes->get('leads/search/(:segment)', 'LeadsController::search/$1'); //get search
	$routes->post('leads', 'LeadsController::create');
	$routes->put('leads/(:segment)', 'LeadsController::update/$1'); //update
	$routes->patch('leads/(:segment)', 'LeadsController::update/$1'); //update
	$routes->delete('leads/(:segment)', 'LeadsController::delete/$1'); //delete

	$routes->get('clients', 'ClientsController::index'); //get
	$routes->get('clients/(:segment)', 'ClientsController::show/$1'); //get by id
	$routes->get('clients/search/(:segment)', 'ClientsController::search/$1'); //get search
	$routes->post('clients', 'ClientsController::create');
	$routes->put('clients/(:segment)', 'ClientsController::update/$1'); //update
	$routes->patch('clients/(:segment)', 'ClientsController::update/$1'); //update
	$routes->delete('clients/(:segment)', 'ClientsController::delete/$1'); //delete

	$routes->get('projects', 'ProjectsController::index'); //get
	$routes->get('projects/(:segment)', 'ProjectsController::show/$1'); //get by id
	$routes->get('projects/search/(:segment)', 'ProjectsController::search/$1'); //get search
	$routes->post('projects', 'ProjectsController::create');
	$routes->put('projects/(:segment)', 'ProjectsController::update/$1'); //update
	$routes->patch('projects/(:segment)', 'ProjectsController::update/$1'); //update
	$routes->delete('projects/(:segment)', 'ProjectsController::delete/$1'); //delete

	$routes->get('tickets', 'TicketsController::index'); //get
	$routes->get('tickets/(:segment)', 'TicketsController::show/$1'); //get by id
	$routes->get('tickets/search/(:segment)', 'TicketsController::search/$1'); //get search
	$routes->post('tickets', 'TicketsController::create');
	$routes->put('tickets/(:segment)', 'TicketsController::update/$1'); //update
	$routes->patch('tickets/(:segment)', 'TicketsController::update/$1'); //update
	$routes->delete('tickets/(:segment)', 'TicketsController::delete/$1'); //delete

	$routes->get('invoices', 'InvoicesController::index'); //get
	$routes->get('invoices/(:segment)', 'InvoicesController::show/$1'); //get by id
	$routes->get('invoices/search/(:segment)', 'InvoicesController::search/$1'); //get search
	$routes->post('invoices', 'InvoicesController::create');
	$routes->put('invoices/(:segment)', 'InvoicesController::update/$1'); //update
	$routes->patch('invoices/(:segment)', 'InvoicesController::update/$1'); //update
	$routes->delete('invoices/(:segment)', 'InvoicesController::delete/$1'); //delete

	// Users Management
	$routes->get('users', 'UsersController::index'); //get
	$routes->get('users/(:segment)', 'UsersController::show/$1'); //get by id
	$routes->get('users/search/(:segment)', 'UsersController::search/$1'); //get search
	$routes->post('users', 'UsersController::create');
	$routes->put('users/(:segment)', 'UsersController::update/$1'); //update
	$routes->patch('users/(:segment)', 'UsersController::update/$1'); //update
	$routes->delete('users/(:segment)', 'UsersController::delete/$1'); //delete

	// Tasks Management
	$routes->get('tasks', 'TasksController::index'); //get
	$routes->get('tasks/(:segment)', 'TasksController::show/$1'); //get by id
	$routes->get('tasks/search/(:segment)', 'TasksController::search/$1'); //get search
	$routes->post('tasks', 'TasksController::create');
	$routes->put('tasks/(:segment)', 'TasksController::update/$1'); //update
	$routes->patch('tasks/(:segment)', 'TasksController::update/$1'); //update
	$routes->delete('tasks/(:segment)', 'TasksController::delete/$1'); //delete

	// Expenses Management
	$routes->get('expenses', 'ExpensesController::index'); //get
	$routes->get('expenses/(:segment)', 'ExpensesController::show/$1'); //get by id
	$routes->get('expenses/search/(:segment)', 'ExpensesController::search/$1'); //get search
	$routes->post('expenses', 'ExpensesController::create');
	$routes->put('expenses/(:segment)', 'ExpensesController::update/$1'); //update
	$routes->patch('expenses/(:segment)', 'ExpensesController::update/$1'); //update
	$routes->delete('expenses/(:segment)', 'ExpensesController::delete/$1'); //delete

	// Estimates Management
	$routes->get('estimates', 'EstimatesController::index'); //get
	$routes->get('estimates/(:segment)', 'EstimatesController::show/$1'); //get by id
	$routes->get('estimates/search/(:segment)', 'EstimatesController::search/$1'); //get search
	$routes->post('estimates', 'EstimatesController::create');
	$routes->put('estimates/(:segment)', 'EstimatesController::update/$1'); //update
	$routes->patch('estimates/(:segment)', 'EstimatesController::update/$1'); //update
	$routes->delete('estimates/(:segment)', 'EstimatesController::delete/$1'); //delete

	// Proposals Management
	$routes->get('proposals', 'ProposalsController::index');
	$routes->get('proposals/(:segment)', 'ProposalsController::show/$1');
	$routes->get('proposals/search/(:segment)', 'ProposalsController::search/$1');
	$routes->post('proposals', 'ProposalsController::create');
	$routes->put('proposals/(:segment)', 'ProposalsController::update/$1');
	$routes->patch('proposals/(:segment)', 'ProposalsController::update/$1');
	$routes->delete('proposals/(:segment)', 'ProposalsController::delete/$1');

	// Orders Management
	$routes->get('orders', 'OrdersController::index');
	$routes->get('orders/(:segment)', 'OrdersController::show/$1');
	$routes->get('orders/search/(:segment)', 'OrdersController::search/$1');
	$routes->post('orders', 'OrdersController::create');
	$routes->put('orders/(:segment)', 'OrdersController::update/$1');
	$routes->patch('orders/(:segment)', 'OrdersController::update/$1');
	$routes->delete('orders/(:segment)', 'OrdersController::delete/$1');

	// Contracts Management
	$routes->get('contracts', 'ContractsController::index');
	$routes->get('contracts/(:segment)', 'ContractsController::show/$1');
	$routes->get('contracts/search/(:segment)', 'ContractsController::search/$1');
	$routes->post('contracts', 'ContractsController::create');
	$routes->put('contracts/(:segment)', 'ContractsController::update/$1');
	$routes->patch('contracts/(:segment)', 'ContractsController::update/$1');
	$routes->delete('contracts/(:segment)', 'ContractsController::delete/$1');

	// Milestones Management
	$routes->get('milestones', 'MilestonesController::index');
	$routes->get('milestones/(:segment)', 'MilestonesController::show/$1');
	$routes->get('milestones/search/(:segment)', 'MilestonesController::search/$1');
	$routes->post('milestones', 'MilestonesController::create');
	$routes->put('milestones/(:segment)', 'MilestonesController::update/$1');
	$routes->patch('milestones/(:segment)', 'MilestonesController::update/$1');
	$routes->delete('milestones/(:segment)', 'MilestonesController::delete/$1');

	// Messages Management
	$routes->get('messages', 'MessagesController::index');
	$routes->get('messages/(:segment)', 'MessagesController::show/$1');
	$routes->get('messages/search/(:segment)', 'MessagesController::search/$1');
	$routes->post('messages', 'MessagesController::create');
	$routes->put('messages/(:segment)', 'MessagesController::update/$1');
	$routes->patch('messages/(:segment)', 'MessagesController::update/$1');
	$routes->delete('messages/(:segment)', 'MessagesController::delete/$1');

	// Notes Management
	$routes->get('notes', 'NotesController::index');
	$routes->get('notes/(:segment)', 'NotesController::show/$1');
	$routes->get('notes/search/(:segment)', 'NotesController::search/$1');
	$routes->post('notes', 'NotesController::create');
	$routes->put('notes/(:segment)', 'NotesController::update/$1');
	$routes->patch('notes/(:segment)', 'NotesController::update/$1');
	$routes->delete('notes/(:segment)', 'NotesController::delete/$1');

	// Events Management
	$routes->get('events', 'EventsController::index');
	$routes->get('events/(:segment)', 'EventsController::show/$1');
	$routes->get('events/search/(:segment)', 'EventsController::search/$1');
	$routes->post('events', 'EventsController::create');
	$routes->put('events/(:segment)', 'EventsController::update/$1');
	$routes->patch('events/(:segment)', 'EventsController::update/$1');
	$routes->delete('events/(:segment)', 'EventsController::delete/$1');

	// Announcements Management
	$routes->get('announcements', 'AnnouncementsController::index');
	$routes->get('announcements/(:segment)', 'AnnouncementsController::show/$1');
	$routes->get('announcements/search/(:segment)', 'AnnouncementsController::search/$1');
	$routes->post('announcements', 'AnnouncementsController::create');
	$routes->put('announcements/(:segment)', 'AnnouncementsController::update/$1');
	$routes->patch('announcements/(:segment)', 'AnnouncementsController::update/$1');
	$routes->delete('announcements/(:segment)', 'AnnouncementsController::delete/$1');

	// Leave Applications Management
	$routes->get('leave-applications', 'LeaveApplicationsController::index');
	$routes->get('leave-applications/(:segment)', 'LeaveApplicationsController::show/$1');
	$routes->get('leave-applications/search/(:segment)', 'LeaveApplicationsController::search/$1');
	$routes->post('leave-applications', 'LeaveApplicationsController::create');
	$routes->put('leave-applications/(:segment)', 'LeaveApplicationsController::update/$1');
	$routes->patch('leave-applications/(:segment)', 'LeaveApplicationsController::update/$1');
	$routes->delete('leave-applications/(:segment)', 'LeaveApplicationsController::delete/$1');

	// ToDo Management
	$routes->get('todos', 'ToDoController::index');
	$routes->get('todos/(:segment)', 'ToDoController::show/$1');
	$routes->get('todos/search/(:segment)', 'ToDoController::search/$1');
	$routes->post('todos', 'ToDoController::create');
	$routes->put('todos/(:segment)', 'ToDoController::update/$1');
	$routes->patch('todos/(:segment)', 'ToDoController::update/$1');
	$routes->delete('todos/(:segment)', 'ToDoController::delete/$1');

	// Invoice Payments Management
	$routes->get('invoice-payments', 'InvoicePaymentsController::index');
	$routes->get('invoice-payments/(:segment)', 'InvoicePaymentsController::show/$1');
	$routes->get('invoice-payments/search/(:segment)', 'InvoicePaymentsController::search/$1');
	$routes->post('invoice-payments', 'InvoicePaymentsController::create');
	$routes->put('invoice-payments/(:segment)', 'InvoicePaymentsController::update/$1');
	$routes->patch('invoice-payments/(:segment)', 'InvoicePaymentsController::update/$1');
	$routes->delete('invoice-payments/(:segment)', 'InvoicePaymentsController::delete/$1');

	// Project Comments Management
	$routes->get('project-comments', 'ProjectCommentsController::index');
	$routes->get('project-comments/(:segment)', 'ProjectCommentsController::show/$1');
	$routes->get('project-comments/search/(:segment)', 'ProjectCommentsController::search/$1');
	$routes->post('project-comments', 'ProjectCommentsController::create');
	$routes->put('project-comments/(:segment)', 'ProjectCommentsController::update/$1');
	$routes->patch('project-comments/(:segment)', 'ProjectCommentsController::update/$1');
	$routes->delete('project-comments/(:segment)', 'ProjectCommentsController::delete/$1');

	// Ticket Comments Management
	$routes->get('ticket-comments', 'TicketCommentsController::index');
	$routes->get('ticket-comments/(:segment)', 'TicketCommentsController::show/$1');
	$routes->get('ticket-comments/search/(:segment)', 'TicketCommentsController::search/$1');
	$routes->post('ticket-comments', 'TicketCommentsController::create');
	$routes->put('ticket-comments/(:segment)', 'TicketCommentsController::update/$1');
	$routes->patch('ticket-comments/(:segment)', 'TicketCommentsController::update/$1');
	$routes->delete('ticket-comments/(:segment)', 'TicketCommentsController::delete/$1');

	// Notifications Management
	$routes->get('notifications', 'NotificationsController::index');
	$routes->get('notifications/(:segment)', 'NotificationsController::show/$1');
	$routes->get('notifications/search/(:segment)', 'NotificationsController::search/$1');
	$routes->post('notifications', 'NotificationsController::create');
	$routes->put('notifications/(:segment)', 'NotificationsController::update/$1');
	$routes->patch('notifications/(:segment)', 'NotificationsController::update/$1');
	$routes->delete('notifications/(:segment)', 'NotificationsController::delete/$1');

	// Activity Logs (Read-Only Audit Trail)
	$routes->get('activity-logs', 'ActivityLogsController::index');
	$routes->get('activity-logs/(:segment)', 'ActivityLogsController::show/$1');
	$routes->get('activity-logs/search/(:segment)', 'ActivityLogsController::search/$1');

	// Attendance Management
	$routes->get('attendance', 'AttendanceController::index');
	$routes->get('attendance/(:segment)', 'AttendanceController::show/$1');
	$routes->get('attendance/search/(:segment)', 'AttendanceController::search/$1');
	$routes->post('attendance', 'AttendanceController::create');
	$routes->put('attendance/(:segment)', 'AttendanceController::update/$1');
	$routes->patch('attendance/(:segment)', 'AttendanceController::update/$1');
	$routes->delete('attendance/(:segment)', 'AttendanceController::delete/$1');

	// Payment Methods Management
	$routes->get('payment-methods', 'PaymentMethodsController::index');
	$routes->get('payment-methods/(:segment)', 'PaymentMethodsController::show/$1');
	$routes->get('payment-methods/search/(:segment)', 'PaymentMethodsController::search/$1');
	$routes->post('payment-methods', 'PaymentMethodsController::create');
	$routes->put('payment-methods/(:segment)', 'PaymentMethodsController::update/$1');
	$routes->patch('payment-methods/(:segment)', 'PaymentMethodsController::update/$1');
	$routes->delete('payment-methods/(:segment)', 'PaymentMethodsController::delete/$1');
});

//Override 404 and give response in JSON format
$routes->set404Override(function ($a) {
	header('Content-Type: application/json');
	echo json_encode([
				"status"  => false,
				"code"    => 404,
				"message" => "Route not found",
			], JSON_PRETTY_PRINT);
	die();
});
