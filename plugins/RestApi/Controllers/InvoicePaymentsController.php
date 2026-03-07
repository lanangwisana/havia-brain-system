<?php

namespace RestApi\Controllers;

class InvoicePaymentsController extends Rest_api_Controller {
	protected $InvoicePaymentsModel = 'RestApi\Models\InvoicePaymentsModel';

	public function __construct() {
		parent::__construct();
		$this->invoice_payments_model = model('App\Models\Invoice_payments_model');
		$this->restapi_invoice_payments_model = model($this->InvoicePaymentsModel);
		$this->invoices_model = model('App\Models\Invoices_model');
		$this->payment_methods_model = model('App\Models\Payment_methods_model');
	}

	/**
	 * @api {get} /api/invoice-payments List all Invoice Payments
	 * @apiVersion 1.0.0
	 * @apiName getInvoicePayments
	 * @apiGroup InvoicePayments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [invoice_id] Optional filter by invoice ID
	 *
	 * @apiSuccess {Object[]} data List of invoice payments
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "invoice_id": "1",
	 *     "amount": "100.00",
	 *     "payment_date": "2024-01-15",
	 *     "payment_method_id": "1"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('invoice_id')) $options['invoice_id'] = $this->request->getGet('invoice_id');

		$list_data = $this->invoice_payments_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/invoice-payments/:id Get Invoice Payment by ID
	 * @apiVersion 1.0.0
	 * @apiName showInvoicePayment
	 * @apiGroup InvoicePayments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Invoice payment information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "invoice_id": "1",
	 *   "amount": "100.00",
	 *   "payment_date": "2024-01-15"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->invoice_payments_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_payment_id'));
	}

	/**
	 * @api {get} /api/invoice-payments/search/:keyword Search Invoice Payments
	 * @apiVersion 1.0.0
	 * @apiName searchInvoicePayments
	 * @apiGroup InvoicePayments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "invoice_id": "1",
	 *     "amount": "100.00"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_invoice_payments_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/invoice-payments Add Invoice Payment
	 * @apiVersion 1.0.0
	 * @apiName createInvoicePayment
	 * @apiGroup InvoicePayments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {number} invoice_id Mandatory Invoice ID
	 * @apiParam (body) {number} amount Mandatory Payment amount
	 * @apiParam (body) {string} payment_date Mandatory Payment date (Y-m-d)
	 * @apiParam (body) {number} [payment_method_id] Optional Payment method ID
	 * @apiParam (body) {string} [note] Optional Payment note
	 * @apiParam (body) {number} [created_by] Optional RISE user ID. Default: 1. Use for proper attribution.
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created payment ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Payment add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = ['invoice_id' => 'required|numeric', 'amount' => 'required|numeric', 'payment_date' => 'required|valid_date[Y-m-d]'];
			if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

			$insert_data = [
				'invoice_id' => $posted_data['invoice_id'],
				'amount' => $posted_data['amount'],
				'payment_date' => $posted_data['payment_date'],
				'payment_method_id' => $posted_data['payment_method_id'] ?? 0,
				'note' => $posted_data['note'] ?? '',
				'created_by' => $this->getCreatedBy(),
				'created_at' => date('Y-m-d H:i:s')
			];

			$data = clean_data($insert_data);
			$save_id = $this->invoice_payments_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('payment_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('payment_add_fail'), 400);
		}
		return $this->fail(app_lang('payment_add_fail'), 400);
	}

	/**
	 * @api {put} api/invoice-payments/:id Update Invoice Payment
	 * @apiVersion 1.0.0
	 * @apiName updateInvoicePayment
	 * @apiGroup InvoicePayments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Payment unique ID
	 * @apiParam (body) {number} [amount] Optional Amount to update
	 * @apiParam (body) {string} [payment_date] Optional Payment date to update
	 * @apiParam (body) {string} [note] Optional Note to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Payment update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_payment_exists = $this->invoice_payments_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_payment_exists)) {
			return $this->fail(app_lang('invalid_payment_id'), 404);
		}

		$update_data = [
			'amount' => $posted_data['amount'] ?? $is_payment_exists['amount'],
			'payment_date' => $posted_data['payment_date'] ?? $is_payment_exists['payment_date'],
			'note' => $posted_data['note'] ?? $is_payment_exists['note']
		];

		$data = clean_data($update_data);
		if ($this->invoice_payments_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('payment_update_success')]]);
		}
		return $this->fail(app_lang('payment_update_fail'), 400);
	}

	/**
	 * @api {delete} api/invoice-payments/:id Delete Invoice Payment
	 * @apiVersion 1.0.0
	 * @apiName deleteInvoicePayment
	 * @apiGroup InvoicePayments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Payment delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->fail(app_lang('invalid_payment_id'), 404);
		
		if ($this->invoice_payments_model->get_details(['id' => $id])->getResult()) {
			if ($this->invoice_payments_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('payment_delete_success')]]);
			}
		}
		return $this->fail(app_lang('payment_delete_fail'), 400);
	}
}
