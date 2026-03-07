<?php

namespace RestApi\Controllers;

class PaymentMethodsController extends Rest_api_Controller {
	protected $PaymentMethodsModel = 'RestApi\Models\PaymentMethodsModel';

	public function __construct() {
		parent::__construct();
		$this->payment_methods_model = model('App\Models\Payment_methods_model');
		$this->restapi_payment_methods_model = model($this->PaymentMethodsModel);
	}

	/**
	 * @api {get} /api/payment-methods List all Payment Methods
	 * @apiVersion 1.0.0
	 * @apiName getPaymentMethods
	 * @apiGroup PaymentMethods
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data List of payment methods
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Bank Transfer",
	 *     "description": "",
	 *     "type": "custom"
	 *   }
	 * ]
	 */
	public function index() {
		$list_data = $this->payment_methods_model->get_details()->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/payment-methods/:id Get Payment Method by ID
	 * @apiVersion 1.0.0
	 * @apiName showPaymentMethod
	 * @apiGroup PaymentMethods
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Payment method information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "title": "Bank Transfer",
	 *   "description": "",
	 *   "type": "custom"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->payment_methods_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_payment_method_id'));
	}

	/**
	 * @api {get} /api/payment-methods/search/:keyword Search Payment Methods
	 * @apiVersion 1.0.0
	 * @apiName searchPaymentMethods
	 * @apiGroup PaymentMethods
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Bank Transfer"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_payment_methods_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/payment-methods Add Payment Method
	 * @apiVersion 1.0.0
	 * @apiName createPaymentMethod
	 * @apiGroup PaymentMethods
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} title Mandatory Payment method title
	 * @apiParam (body) {string} [description] Optional Payment method description
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created payment method ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Payment method add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = ['title' => 'required'];
			if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

			$insert_data = [
				'title' => $posted_data['title'],
				'description' => $posted_data['description'] ?? '',
				'type' => $posted_data['type'] ?? 'custom'
			];

			$data = clean_data($insert_data);
			$save_id = $this->payment_methods_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('payment_method_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('payment_method_add_fail'), 400);
		}
		return $this->fail(app_lang('payment_method_add_fail'), 400);
	}

	/**
	 * @api {put} api/payment-methods/:id Update Payment Method
	 * @apiVersion 1.0.0
	 * @apiName updatePaymentMethod
	 * @apiGroup PaymentMethods
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Payment method unique ID
	 * @apiParam (body) {string} [title] Optional Title to update
	 * @apiParam (body) {string} [description] Optional Description to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Payment method update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_payment_method_exists = $this->payment_methods_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_payment_method_exists)) {
			return $this->fail(app_lang('invalid_payment_method_id'), 404);
		}

		$update_data = [
			'title' => $posted_data['title'] ?? $is_payment_method_exists['title'],
			'description' => $posted_data['description'] ?? $is_payment_method_exists['description']
		];

		$data = clean_data($update_data);
		if ($this->payment_methods_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('payment_method_update_success')]]);
		}
		return $this->fail(app_lang('payment_method_update_fail'), 400);
	}

	/**
	 * @api {delete} api/payment-methods/:id Delete Payment Method
	 * @apiVersion 1.0.0
	 * @apiName deletePaymentMethod
	 * @apiGroup PaymentMethods
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Payment method delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->fail(app_lang('invalid_payment_method_id'), 404);
		
		if ($this->payment_methods_model->get_details(['id' => $id])->getResult()) {
			if ($this->payment_methods_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('payment_method_delete_success')]]);
			}
		}
		return $this->fail(app_lang('payment_method_delete_fail'), 400);
	}
}
