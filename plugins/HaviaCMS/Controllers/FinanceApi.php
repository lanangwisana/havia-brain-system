<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class FinanceApi extends ResourceController {
    use ResponseTrait;

    protected $format = 'json';
    protected $projects_model;
    protected $expenses_model;
    protected $invoices_model;
    protected $invoice_payments_table;
    protected $users_model;
    protected $api_settings_model;
    protected $settings_model;
    protected $db;
    private $initialized = false;

    public function __construct() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, authtoken, Accept");

        if (strtoupper(request()->getMethod()) === 'OPTIONS') {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

    private function _init() {
        if ($this->initialized) return;
        
        helper(['date_time', 'general', 'url']);
        
        if (defined('PLUGINPATH') && file_exists(PLUGINPATH . "RestApi/Helpers/jwt_helper.php")) {
            require_once PLUGINPATH . "RestApi/Helpers/jwt_helper.php";
        }

        $this->projects_model = model('App\Models\Projects_model');
        $this->expenses_model = model('App\Models\Expenses_model');
        $this->invoices_model = model('App\Models\Invoices_model');
        $this->users_model = model('App\Models\Users_model');
        $this->api_settings_model = model('RestApi\Models\Api_settings_model');
        $this->settings_model = model('App\Models\Settings_model');

        $this->db = \Config\Database::connect();
        $this->invoice_payments_table = $this->db->prefixTable('invoice_payments');

        $this->initialized = true;
    }

    private function _validate_user() {
        $token_raw = null;
        $all_headers = $this->request->getHeaders();
        
        foreach($all_headers as $name => $header) {
            if (strtolower($name) === 'authtoken' || strtolower($name) === 'authorization') {
                $token_raw = (string)$header;
                break;
            }
        }

        if (!$token_raw) return "ERROR_MISSING_HEADER";

        $token = $token_raw;
        while (preg_match('/^(authtoken|authorization|bearer):?\s+/i', $token)) {
            $token = preg_replace('/^(authtoken|authorization|bearer):?\s+/i', '', $token);
        }
        $token = trim($token);

        if (empty($token)) return "ERROR_EMPTY_TOKEN";

        try {
            $jwt_config = new \RestApi\Config\JWT();
            $key = preg_replace('/^["\']|["\']$/', '', trim($jwt_config->jwt_key));
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, $jwt_config->jwt_algorithm));
            
            if ($decoded && is_object($decoded)) {
                $user_id = $decoded->id ?? $decoded->crm_user_id ?? null;
                if ($user_id) {
                    $user = $this->users_model->get_one($user_id);
                    if ($user && $user->id && !$user->deleted) return (int)$user_id;
                }
            }
        } catch (\Exception $e) {}

        $api_user = $this->api_settings_model->get_one_where(['token' => $token]);
        if ($api_user && isset($api_user->user)) {
            $user_row = $this->users_model->get_one_where(['email' => $api_user->user, 'deleted' => 0]);
            if ($user_row && $user_row->id) return (int)$user_row->id;
        }

        return "ERROR_AUTH_FAILED";
    }

    public function summary() {
        try {
            $this->_init();
            $validation = $this->_validate_user();
            if (!is_int($validation)) return $this->failUnauthorized($validation);

            $user_id = $validation;
            $user = $this->users_model->get_one($user_id);
            
            // 1. Get projects. 
            // We'll fetch all non-deleted projects the user has access to.
            // For now, let's fetch all projects if it's a staff member to ensure they see the data they configured.
            $options = array();
            if ($user->user_type === "client") {
                $options["client_id"] = $user->client_id;
            } else if (!$user->is_admin) {
                // For non-admin staff, we usually show projects they are members of.
                // But let's check if the user wants to see all projects they have access to.
                $options["user_id"] = $user_id;
            }
            
            $projects = $this->projects_model->get_details($options)->getResultArray();

            $summary_data = [];
            foreach ($projects as $project) {
                $project_id = $project['id'];
                
                // 2. Get Expenses specifically for this project
                $expenses = $this->expenses_model->get_details(['project_id' => $project_id])->getResultArray();
                $total_expense = 0;
                foreach ($expenses as $exp) {
                    $amt = (float)$exp['amount'];
                    $tax_percentage = (float)($exp['tax_percentage'] ?? 0);
                    $tax_percentage2 = (float)($exp['tax_percentage2'] ?? 0);
                    
                    $tax = ($tax_percentage / 100) * $amt;
                    $tax2 = ($tax_percentage2 / 100) * $amt;
                    
                    $total_expense += ($amt + $tax + $tax2);
                }

                // 3. Get Project Progress (Tasks)
                $progress = 0;
                if (isset($project['total_points']) && $project['total_points'] > 0) {
                    $progress = round(($project['completed_points'] / $project['total_points']) * 100);
                } else if (isset($project['total_tasks']) && $project['total_tasks'] > 0) {
                    $progress = round(($project['completed_tasks'] / $project['total_tasks']) * 100);
                }

                $project_price = (float)($project['price'] ?? 0);
                $balance = $project_price - $total_expense;
                
                // Only include if either price is set or there are expenses (matches user's "financial records" expectation)
                if ($project_price > 0 || $total_expense > 0) {
                    $summary_data[] = [
                        'project_id' => $project_id,
                        'project_title' => $project['title'],
                        'project_price' => $project_price,
                        'total_expense' => $total_expense,
                        'balance' => $balance,
                        'progress' => $progress,
                        'expense_ratio' => $project_price > 0 ? round(($total_expense / $project_price) * 100, 2) : 0,
                        'status_title' => $project['status_title'] ?? 'Open'
                    ];
                }
            }

            // Sort by project_id DESC to show newest projects first
            usort($summary_data, function($a, $b) {
                return (int)$b['project_id'] - (int)$a['project_id'];
            });

            return $this->respond([
                "success" => true,
                "data" => $summary_data
            ]);

        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
