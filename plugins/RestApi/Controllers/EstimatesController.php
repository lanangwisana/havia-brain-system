<?php

namespace RestApi\Controllers;

class EstimatesController extends Rest_api_Controller {
	protected $EstimatesModel = 'RestApi\Models\EstimatesModel';

	public function __construct() {
		parent::__construct();

		$this->estimates_model = model('App\Models\Estimates_model');
		$this->restapi_estimates_model = model($this->EstimatesModel);
		$this->clients_model = model('App\Models\Clients_model');
		$this->projects_model = model('App\Models\Projects_model');
		$this->taxes_model = model('App\Models\Taxes_model');
	}

	/**
	 * @api {get} /api/estimates List all Estimates
	 * @apiVersion 1.0.0
	 * @apiName getEstimates
	 * @apiGroup Estimates
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [client_id] Optional filter by client ID
	 * @apiParam (query) {String} [status] Optional filter by status (draft, sent, accepted, declined)
	 *
	 * @apiSuccess {Object} Estimates information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"client_id": "2",
	 * 		"estimate_date": "2021-09-15",
	 * 		"valid_until": "2021-10-15",
	 * 		"status": "sent",
	 * 		"note": "Estimate for new project",
	 * 		"tax_id": "1",
	 * 		"tax_id2": "0",
	 * 		"discount_amount": "10",
	 * 		"discount_amount_type": "percentage",
	 * 		"project_id": "1"
	 * }
	 *
	 */
	public function index() {
		$options = [];
		
		if ($this->request->getGet('client_id')) {
			$options['client_id'] = $this->request->getGet('client_id');
		}
		
		if ($this->request->getGet('status')) {
			$options['status'] = $this->request->getGet('status');
		}

		$list_data = $this->estimates_model->get_details($options)->getResult();
		
		if (empty($list_data)) {
			return $this->failNotFound(app_lang('no_data_were_found'));
		}

		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/estimates/:id Get Estimate by ID
	 * @apiVersion 1.0.0
	 * @apiName showEstimate
	 * @apiGroup Estimates
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Estimate unique ID
	 *
	 * @apiSuccess {Object} Estimate information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"client_id": "2",
	 * 		"estimate_date": "2021-09-15",
	 * 		"valid_until": "2021-10-15",
	 * 		"status": "sent",
	 * 		"note": "Estimate for new project",
	 * 		"tax_id": "1",
	 * 		"tax_id2": "0",
	 * 		"discount_amount": "10",
	 * 		"discount_amount_type": "percentage",
	 * 		"project_id": "1"
	 * }
	 *
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->estimates_model->get_details(['id' => $id])->getRow();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('invalid_estimate_id'));
	}

	/**
	 * @api {get} /api/estimates/search/:keyword Search Estimates
	 * @apiVersion 1.0.0
	 * @apiName searchEstimates
	 * @apiGroup Estimates
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {String} keyword Search keyword
	 *
	 * @apiSuccess {Object} Estimates information
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "client_id": "2",
	 *     "estimate_date": "2021-09-15",
	 *     "status": "sent",
	 *     "note": "Estimate for project"
	 *   }
	 * ]
	 *
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_estimates_model->get_search_suggestion($key)->getResult();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/estimates Add New Estimate
	 * @apiVersion 1.0.0
	 * @apiName createEstimate
	 * @apiGroup Estimates
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {number} client_id Mandatory Client ID
	 * @apiParam (body) {string} estimate_date Mandatory Estimate date (Y-m-d)
	 * @apiParam (body) {string} valid_until Mandatory Valid until date (Y-m-d)
	 * @apiParam (body) {string} [status] Optional Status (draft, sent, accepted, declined) default: draft
	 * @apiParam (body) {string} [note] Optional Estimate note
	 * @apiParam (body) {number} [project_id] Optional Project ID
	 * @apiParam (body) {number} [tax_id] Optional Tax ID
	 * @apiParam (body) {number} [tax_id2] Optional Second tax ID
	 * @apiParam (body) {number} [discount_amount] Optional Discount amount
	 * @apiParam (body) {string} [discount_amount_type] Optional Discount type (percentage, fixed_amount)
	 * @apiParam (body) {number} [created_by] Optional RISE user ID. Default: 1. Use for proper attribution.
	 *
	 * @apiParamExample Request-Example:
	 *     {
	 *        "client_id": 2,
	 *        "estimate_date": "2021-09-15",
	 *        "valid_until": "2021-10-15",
	 *        "status": "draft",
	 *        "note": "Estimate for project",
	 *        "project_id": 1,
	 *        "tax_id": 1,
	 *        "discount_amount": 10,
	 *        "discount_amount_type": "percentage"
	 *     }
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Estimate add successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Estimate add successful."
	 *     }
	 *
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		
		if (!empty($posted_data)) {
			$rules = [
				'client_id'      => 'required|numeric',
				'estimate_date'  => 'required|valid_date[Y-m-d]',
				'valid_until'    => 'required|valid_date[Y-m-d]',
				'status'         => 'in_list[draft,sent,accepted,declined]|if_exist',
				'project_id'     => 'numeric|if_exist',
				'tax_id'         => 'numeric|if_exist',
				'tax_id2'        => 'numeric|if_exist',
				'discount_amount'=> 'numeric|if_exist',
				'discount_amount_type' => 'in_list[percentage,fixed_amount]|if_exist'
			];

			$error = [
				'client_id' => [
					'required' => app_lang('client_id_required'),
					'numeric'  => app_lang('invalid_client_id')
				],
				'estimate_date' => [
					'required'   => app_lang('estimate_date_required'),
					'valid_date' => app_lang('invalid_estimate_date')
				],
				'valid_until' => [
					'required'   => app_lang('valid_until_required'),
					'valid_date' => app_lang('invalid_valid_until')
				],
				'status' => [
					'in_list' => app_lang('invalid_estimate_status')
				],
				'project_id' => [
					'numeric' => app_lang('invalid_project_id')
				],
				'tax_id' => [
					'numeric' => app_lang('invalid_tax_id')
				],
				'tax_id2' => [
					'numeric' => app_lang('invalid_tax_id')
				],
				'discount_amount' => [
					'numeric' => app_lang('invalid_discount_amount')
				],
				'discount_amount_type' => [
					'in_list' => app_lang('invalid_discount_type')
				]
			];

			if (!$this->validate($rules, $error)) {
				return $this->failValidationErrors($this->validator->getErrors());
			}

			// Validate client exists
			$is_client_exists = $this->clients_model->get_details(['id' => $posted_data['client_id']])->getResult();
			if (empty($is_client_exists)) {
				return $this->failValidationError(app_lang('invalid_client_id'));
			}

			$insert_data = [
				'client_id'      => $posted_data['client_id'],
				'estimate_date'  => $posted_data['estimate_date'],
				'valid_until'    => $posted_data['valid_until'],
				'status'         => $posted_data['status'] ?? 'draft',
				'note'           => $posted_data['note'] ?? '',
				'project_id'     => $posted_data['project_id'] ?? 0,
				'tax_id'         => $posted_data['tax_id'] ?? 0,
				'tax_id2'        => $posted_data['tax_id2'] ?? 0,
				'discount_amount'=> $posted_data['discount_amount'] ?? 0,
				'discount_amount_type' => $posted_data['discount_amount_type'] ?? 'percentage',
				'discount_type'  => 'after_tax',
				'created_by'     => $this->getCreatedBy()
			];

			$data = clean_data($insert_data);

			$save_id = $this->estimates_model->ci_save($data);
			
			if ($save_id > 0 && !empty($save_id)) {
				$response = [
					'status'   => 200,
					'messages' => [
						'success' => app_lang('estimate_add_success')
					],
					'id' => $save_id
				];
				return $this->respondCreated($response);
			}
			
			return $this->fail(app_lang('estimate_add_fail'), 400);
		}
		
		return $this->fail(app_lang('estimate_add_fail'), 400);
	}

	/**
	 * @api {put} api/estimates/:id Update an Estimate
	 * @apiVersion 1.0.0
	 * @apiName updateEstimate
	 * @apiGroup Estimates
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Estimate unique ID
	 * @apiParam (body) {number} [client_id] Optional Client ID to update
	 * @apiParam (body) {string} [estimate_date] Optional Estimate date to update
	 * @apiParam (body) {string} [valid_until] Optional Valid until date to update
	 * @apiParam (body) {string} [status] Optional Status to update
	 * @apiParam (body) {string} [note] Optional Note to update
	 * @apiParam (body) {number} [project_id] Optional Project ID to update
	 * @apiParam (body) {number} [tax_id] Optional Tax ID to update
	 * @apiParam (body) {number} [discount_amount] Optional Discount amount to update
	 *
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	    "estimate_date": "2021-09-20",
	 *	    "valid_until": "2021-10-20",
	 *	    "status": "sent",
	 *	    "note": "Updated note"
	 *	}
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Estimate Update Successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Estimate Update Successful."
	 *     }
	 *
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_estimate_exists = $this->estimates_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_estimate_exists)) {
			return $this->fail(app_lang('invalid_estimate_id'), 404);
		}

		$rules = [
			'client_id'      => 'numeric|if_exist',
			'estimate_date'  => 'valid_date[Y-m-d]|if_exist',
			'valid_until'    => 'valid_date[Y-m-d]|if_exist',
			'status'         => 'in_list[draft,sent,accepted,declined]|if_exist',
			'project_id'     => 'numeric|if_exist',
			'tax_id'         => 'numeric|if_exist',
			'tax_id2'        => 'numeric|if_exist',
			'discount_amount'=> 'numeric|if_exist'
		];

		$error = [
			'client_id' => [
				'numeric' => app_lang('invalid_client_id')
			],
			'estimate_date' => [
				'valid_date' => app_lang('invalid_estimate_date')
			],
			'valid_until' => [
				'valid_date' => app_lang('invalid_valid_until')
			],
			'status' => [
				'in_list' => app_lang('invalid_estimate_status')
			],
			'project_id' => [
				'numeric' => app_lang('invalid_project_id')
			],
			'tax_id' => [
				'numeric' => app_lang('invalid_tax_id')
			],
			'tax_id2' => [
				'numeric' => app_lang('invalid_tax_id')
			],
			'discount_amount' => [
				'numeric' => app_lang('invalid_discount_amount')
			]
		];

		if (!$this->validate($rules, $error)) {
			return $this->failValidationErrors($this->validator->getErrors());
		}

		$update_data = [
			'client_id'      => $posted_data['client_id'] ?? $is_estimate_exists['client_id'],
			'estimate_date'  => $posted_data['estimate_date'] ?? $is_estimate_exists['estimate_date'],
			'valid_until'    => $posted_data['valid_until'] ?? $is_estimate_exists['valid_until'],
			'status'         => $posted_data['status'] ?? $is_estimate_exists['status'],
			'note'           => $posted_data['note'] ?? $is_estimate_exists['note'],
			'project_id'     => $posted_data['project_id'] ?? $is_estimate_exists['project_id'],
			'tax_id'         => $posted_data['tax_id'] ?? $is_estimate_exists['tax_id'],
			'tax_id2'        => $posted_data['tax_id2'] ?? $is_estimate_exists['tax_id2'],
			'discount_amount'=> $posted_data['discount_amount'] ?? $is_estimate_exists['discount_amount'],
		];

		$data = clean_data($update_data);

		if ($this->estimates_model->ci_save($data, $id)) {
			$response = [
				'status'   => 200,
				'messages' => [
					'success' => app_lang('estimate_update_success')
				]
			];
			return $this->respondCreated($response);
		}
		
		return $this->fail(app_lang('estimate_update_fail'), 400);
	}

	/**
	 * @api {delete} api/estimates/:id Delete an Estimate
	 * @apiVersion 1.0.0
	 * @apiName deleteEstimate
	 * @apiGroup Estimates
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Estimate unique ID
	 *
	 * @apiSuccess {String} status Request status.
	 * @apiSuccess {String} message Estimate Deleted Successfully.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Estimate Deleted Successfully."
	 *     }
	 *
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) {
			return $this->fail(app_lang('invalid_estimate_id'), 404);
		}
		
		if ($this->estimates_model->get_details(['id' => $id])->getResult()) {
			if ($this->estimates_model->delete($id)) {
				$response = [
					'status'   => 200,
					'messages' => [
						'success' => app_lang('estimate_delete_success')
					]
				];
				return $this->respondDeleted($response);
			}
			
			return $this->fail(app_lang('estimate_delete_fail'), 400);
		}
		
		return $this->fail(app_lang('estimate_delete_fail'), 400);
	}
}
