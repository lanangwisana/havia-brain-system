<?php

namespace RestApi\Controllers;

class ExpensesController extends Rest_api_Controller {
	protected $ExpensesModel = 'RestApi\Models\ExpensesModel';

	public function __construct() {
		parent::__construct();

		$this->expenses_model = model('App\Models\Expenses_model');
		$this->restapi_expenses_model = model($this->ExpensesModel);
		$this->expense_categories_model = model('App\Models\Expense_categories_model');
		$this->projects_model = model('App\Models\Projects_model');
		$this->users_model = model('App\Models\Users_model');
		$this->clients_model = model('App\Models\Clients_model');
		$this->taxes_model = model('App\Models\Taxes_model');
	}

	/**
	 * @api {get} /api/expenses List all Expenses
	 * @apiVersion 1.0.0
	 * @apiName getExpenses
	 * @apiGroup Expenses
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [project_id] Optional filter by project ID
	 * @apiParam (query) {Number} [category_id] Optional filter by category ID
	 * @apiParam (query) {Number} [user_id] Optional filter by user ID
	 *
	 * @apiSuccess {Object} Expenses information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"expense_date": "2021-09-15",
	 * 		"category_id": "1",
	 * 		"description": "Office supplies",
	 * 		"amount": "250.00",
	 * 		"title": "Monthly Office Expenses",
	 * 		"project_id": "1",
	 * 		"user_id": "2",
	 * 		"tax_id": "1",
	 * 		"client_id": "1"
	 * }
	 *
	 */
	public function index() {
		$options = [];
		
		if ($this->request->getGet('project_id')) {
			$options['project_id'] = $this->request->getGet('project_id');
		}
		
		if ($this->request->getGet('category_id')) {
			$options['category_id'] = $this->request->getGet('category_id');
		}
		
		if ($this->request->getGet('user_id')) {
			$options['user_id'] = $this->request->getGet('user_id');
		}

		$list_data = $this->expenses_model->get_details($options)->getResult();
		
		if (empty($list_data)) {
			return $this->failNotFound(app_lang('no_data_were_found'));
		}

		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/expenses/:id Get Expense by ID
	 * @apiVersion 1.0.0
	 * @apiName showExpense
	 * @apiGroup Expenses
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Expense unique ID
	 *
	 * @apiSuccess {Object} Expense information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"expense_date": "2021-09-15",
	 * 		"category_id": "1",
	 * 		"description": "Office supplies",
	 * 		"amount": "250.00",
	 * 		"title": "Monthly Office Expenses",
	 * 		"project_id": "1",
	 * 		"user_id": "2",
	 * 		"tax_id": "1",
	 * 		"client_id": "1"
	 * }
	 *
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->expenses_model->get_details(['id' => $id])->getRow();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('invalid_expense_id'));
	}

	/**
	 * @api {get} /api/expenses/search/:keyword Search Expenses
	 * @apiVersion 1.0.0
	 * @apiName searchExpenses
	 * @apiGroup Expenses
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {String} keyword Search keyword
	 *
	 * @apiSuccess {Object} Expenses information
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Office Supplies",
	 *     "amount": "250.00",
	 *     "expense_date": "2021-09-15",
	 *     "category_id": "1"
	 *   }
	 * ]
	 *
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_expenses_model->get_search_suggestion($key)->getResult();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/expenses Add New Expense
	 * @apiVersion 1.0.0
	 * @apiName createExpense
	 * @apiGroup Expenses
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} expense_date Mandatory Expense date (Y-m-d)
	 * @apiParam (body) {number} category_id Mandatory Category ID
	 * @apiParam (body) {string} title Mandatory Expense title
	 * @apiParam (body) {number} amount Mandatory Expense amount
	 * @apiParam (body) {string} [description] Optional Expense description
	 * @apiParam (body) {number} [project_id] Optional Project ID
	 * @apiParam (body) {number} [user_id] Optional User ID
	 * @apiParam (body) {number} [client_id] Optional Client ID
	 * @apiParam (body) {number} [tax_id] Optional Tax ID
	 * @apiParam (body) {number} [tax_id2] Optional Second tax ID
	 * @apiParam (body) {number} [created_by] Optional RISE user ID. Default: 1. Use for proper attribution.
	 *
	 * @apiParamExample Request-Example:
	 *     {
	 *        "expense_date": "2021-09-15",
	 *        "category_id": 1,
	 *        "title": "Office Supplies",
	 *        "amount": 250.00,
	 *        "description": "Pens, papers, folders",
	 *        "project_id": 1,
	 *        "user_id": 2,
	 *        "tax_id": 1
	 *     }
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Expense add successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Expense add successful."
	 *     }
	 *
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		
		if (!empty($posted_data)) {
			$rules = [
				'expense_date' => 'required|valid_date[Y-m-d]',
				'category_id'  => 'required|numeric',
				'title'        => 'required',
				'amount'       => 'required|numeric',
				'project_id'   => 'numeric|if_exist',
				'user_id'      => 'numeric|if_exist',
				'client_id'    => 'numeric|if_exist',
				'tax_id'       => 'numeric|if_exist',
				'tax_id2'      => 'numeric|if_exist'
			];

			$error = [
				'expense_date' => [
					'required'   => app_lang('expense_date_required'),
					'valid_date' => app_lang('invalid_expense_date')
				],
				'category_id' => [
					'required' => app_lang('category_id_required'),
					'numeric'  => app_lang('invalid_category_id')
				],
				'title' => [
					'required' => app_lang('expense_title_required')
				],
				'amount' => [
					'required' => app_lang('amount_required'),
					'numeric'  => app_lang('invalid_amount')
				],
				'project_id' => [
					'numeric' => app_lang('invalid_project_id')
				],
				'user_id' => [
					'numeric' => app_lang('invalid_user_id')
				],
				'client_id' => [
					'numeric' => app_lang('invalid_client_id')
				],
				'tax_id' => [
					'numeric' => app_lang('invalid_tax_id')
				],
				'tax_id2' => [
					'numeric' => app_lang('invalid_tax_id')
				]
			];

			if (!$this->validate($rules, $error)) {
				return $this->failValidationErrors($this->validator->getErrors());
			}

			// Validate category exists
			$is_category_exists = $this->expense_categories_model->get_details(['id' => $posted_data['category_id']])->getResult();
			if (empty($is_category_exists)) {
				return $this->failValidationError(app_lang('invalid_category_id'));
			}

			// Validate project if provided
			if (isset($posted_data['project_id']) && $posted_data['project_id'] > 0) {
				$is_project_exists = $this->projects_model->get_details(['id' => $posted_data['project_id']])->getResult();
				if (empty($is_project_exists)) {
					return $this->failValidationError(app_lang('invalid_project_id'));
				}
			}

			// Validate user if provided
			if (isset($posted_data['user_id']) && $posted_data['user_id'] > 0) {
				$is_user_exists = $this->users_model->get_details(['id' => $posted_data['user_id']])->getResult();
				if (empty($is_user_exists)) {
					return $this->failValidationError(app_lang('invalid_user_id'));
				}
			}

			// Validate client if provided
			if (isset($posted_data['client_id']) && $posted_data['client_id'] > 0) {
				$is_client_exists = $this->clients_model->get_details(['id' => $posted_data['client_id']])->getResult();
				if (empty($is_client_exists)) {
					return $this->failValidationError(app_lang('invalid_client_id'));
				}
			}

			$insert_data = [
				'expense_date' => $posted_data['expense_date'],
				'category_id'  => $posted_data['category_id'],
				'title'        => $posted_data['title'],
				'amount'       => $posted_data['amount'],
				'description'  => $posted_data['description'] ?? '',
				'project_id'   => $posted_data['project_id'] ?? 0,
				'user_id'      => $posted_data['user_id'] ?? 0,
				'client_id'    => $posted_data['client_id'] ?? 0,
				'tax_id'       => $posted_data['tax_id'] ?? 0,
				'tax_id2'      => $posted_data['tax_id2'] ?? 0,
				'created_by'   => $this->getCreatedBy(),
				'files' => serialize([])
			];

			$data = clean_data($insert_data);

			$save_id = $this->expenses_model->ci_save($data);
			
			if ($save_id > 0 && !empty($save_id)) {
				$response = [
					'status'   => 200,
					'messages' => [
						'success' => app_lang('expense_add_success')
					],
					'id' => $save_id
				];
				return $this->respondCreated($response);
			}
			
			return $this->fail(app_lang('expense_add_fail'), 400);
		}
		
		return $this->fail(app_lang('expense_add_fail'), 400);
	}

	/**
	 * @api {put} api/expenses/:id Update an Expense
	 * @apiVersion 1.0.0
	 * @apiName updateExpense
	 * @apiGroup Expenses
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Expense unique ID
	 * @apiParam (body) {string} [expense_date] Optional Expense date
	 * @apiParam (body) {number} [category_id] Optional Category ID
	 * @apiParam (body) {string} [title] Optional Expense title
	 * @apiParam (body) {number} [amount] Optional Expense amount
	 * @apiParam (body) {string} [description] Optional Expense description
	 *
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	    "title": "Updated Expense Title",
	 *	    "amount": 300.00,
	 *	    "description": "Updated description",
	 *	    "tax_id": 2
	 *	}
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Expense Update Successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Expense Update Successful."
	 *     }
	 *
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_expense_exists = $this->expenses_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_expense_exists)) {
			$response = [
				'messages' => [
					'success' => app_lang('invalid_expense_id')
				]
			];
			return $this->fail($response);
		}

		$rules = [
			'expense_date' => 'valid_date[Y-m-d]|if_exist',
			'category_id'  => 'numeric|if_exist',
			'title'        => 'required|if_exist',
			'amount'       => 'numeric|if_exist',
			'project_id'   => 'numeric|if_exist',
			'user_id'      => 'numeric|if_exist',
			'client_id'    => 'numeric|if_exist',
			'tax_id'       => 'numeric|if_exist',
			'tax_id2'      => 'numeric|if_exist'
		];

		$error = [
			'expense_date' => [
				'valid_date' => app_lang('invalid_expense_date')
			],
			'category_id' => [
				'numeric' => app_lang('invalid_category_id')
			],
			'title' => [
				'required' => app_lang('expense_title_required')
			],
			'amount' => [
				'numeric' => app_lang('invalid_amount')
			],
			'project_id' => [
				'numeric' => app_lang('invalid_project_id')
			],
			'user_id' => [
				'numeric' => app_lang('invalid_user_id')
			],
			'client_id' => [
				'numeric' => app_lang('invalid_client_id')
			],
			'tax_id' => [
				'numeric' => app_lang('invalid_tax_id')
			],
			'tax_id2' => [
				'numeric' => app_lang('invalid_tax_id')
			]
		];

		if (!$this->validate($rules, $error)) {
			return $this->failValidationErrors($this->validator->getErrors());
		}

		// Validate category if provided
		if (isset($posted_data['category_id'])) {
			$is_category_exists = $this->expense_categories_model->get_details(['id' => $posted_data['category_id']])->getResult();
			if (empty($is_category_exists)) {
				return $this->failValidationError(app_lang('invalid_category_id'));
			}
		}

		$update_data = [
			'expense_date' => $posted_data['expense_date'] ?? $is_expense_exists['expense_date'],
			'category_id'  => $posted_data['category_id'] ?? $is_expense_exists['category_id'],
			'title'        => $posted_data['title'] ?? $is_expense_exists['title'],
			'amount'       => $posted_data['amount'] ?? $is_expense_exists['amount'],
			'description'  => $posted_data['description'] ?? $is_expense_exists['description'],
			'project_id'   => $posted_data['project_id'] ?? $is_expense_exists['project_id'],
			'user_id'      => $posted_data['user_id'] ?? $is_expense_exists['user_id'],
			'client_id'    => $posted_data['client_id'] ?? $is_expense_exists['client_id'],
			'tax_id'       => $posted_data['tax_id'] ?? $is_expense_exists['tax_id'],
			'tax_id2'      => $posted_data['tax_id2'] ?? $is_expense_exists['tax_id2'],
		];

		$data = clean_data($update_data);

		if ($this->expenses_model->ci_save($data, $id)) {
			$response = [
				'status'   => 200,
				'messages' => [
					'success' => app_lang('expense_update_success')
				]
			];
			return $this->respondCreated($response);
		}
		
		$response = [
			'messages' => [
				'success' => app_lang('expense_update_fail')
			]
		];
		return $this->fail($response);
	}

	/**
	 * @api {delete} api/expenses/:id Delete an Expense
	 * @apiVersion 1.0.0
	 * @apiName deleteExpense
	 * @apiGroup Expenses
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Expense unique ID
	 *
	 * @apiSuccess {String} status Request status.
	 * @apiSuccess {String} message Expense Deleted Successfully.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Expense Deleted Successfully."
	 *     }
	 *
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) {
			return $this->fail(app_lang('invalid_expense_id'), 404);
		}
		
		if ($this->expenses_model->get_details(['id' => $id])->getResult()) {
			if ($this->expenses_model->delete($id)) {
				$response = [
					'status'   => 200,
					'messages' => [
						'success' => app_lang('expense_delete_success')
					]
				];
				return $this->respondDeleted($response);
			}
			
			return $this->fail(app_lang('expense_delete_fail'), 400);
		}
		
		return $this->fail(app_lang('expense_delete_fail'), 400);
	}
}
