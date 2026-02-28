<?php

namespace RestApi\Controllers;

class OrdersController extends Rest_api_Controller {
	protected $OrdersModel = 'RestApi\Models\OrdersModel';

	public function __construct() {
		parent::__construct();
		$this->orders_model = model('App\Models\Orders_model');
		$this->restapi_orders_model = model($this->OrdersModel);
		$this->clients_model = model('App\Models\Clients_model');
	}

	/**
	 * @api {get} /api/orders List all Orders
	 * @apiVersion 1.0.0
	 * @apiName getOrders
	 * @apiGroup Orders
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [client_id] Optional filter by client ID
	 * @apiParam (query) {Number} [status_id] Optional filter by status ID
	 *
	 * @apiSuccess {Object[]} data List of orders
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "client_id": "2",
	 *     "order_date": "2024-01-15",
	 *     "status_id": "1",
	 *     "note": "",
	 *     "project_id": "0",
	 *     "discount_amount": "0"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('client_id')) $options['client_id'] = $this->request->getGet('client_id');
		if ($this->request->getGet('status_id')) $options['status_id'] = $this->request->getGet('status_id');

		$list_data = $this->orders_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/orders/:id Get Order by ID
	 * @apiVersion 1.0.0
	 * @apiName showOrder
	 * @apiGroup Orders
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Order information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "client_id": "2",
	 *   "order_date": "2024-01-15",
	 *   "status_id": "1",
	 *   "note": "",
	 *   "project_id": "0"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->orders_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_order_id'));
	}

	/**
	 * @api {get} /api/orders/search/:keyword Search Orders
	 * @apiVersion 1.0.0
	 * @apiName searchOrders
	 * @apiGroup Orders
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results (orders)
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Order #1"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_orders_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/orders Add New Order
	 * @apiVersion 1.0.0
	 * @apiName createOrder
	 * @apiGroup Orders
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {number} client_id Mandatory Client ID
	 * @apiParam (body) {string} order_date Mandatory Order date (Y-m-d)
	 * @apiParam (body) {number} status_id Mandatory Status ID
	 * @apiParam (body) {string} [note] Optional Order note
	 * @apiParam (body) {number} [project_id] Optional Project ID
	 * @apiParam (body) {number} [discount_amount] Optional Discount amount
	 * @apiParam (body) {number} [created_by] Optional RISE user ID. Default: 1. Use for proper attribution.
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created order ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Order add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = [
				'client_id'  => 'required|numeric',
				'order_date' => 'required|valid_date[Y-m-d]',
				'status_id'  => 'required|numeric'
			];

			if (!$this->validate($rules)) {
				return $this->failValidationErrors($this->validator->getErrors());
			}

			$insert_data = [
				'client_id'  => $posted_data['client_id'],
				'order_date' => $posted_data['order_date'],
				'status_id'  => $posted_data['status_id'],
				'note'       => $posted_data['note'] ?? '',
				'project_id' => $posted_data['project_id'] ?? 0,
				'discount_amount' => $posted_data['discount_amount'] ?? 0,
				'discount_amount_type' => $posted_data['discount_amount_type'] ?? 'percentage',
				'discount_type' => 'after_tax',
				'created_by' => $this->getCreatedBy(),
				'files' => serialize([])
			];

			$data = clean_data($insert_data);
			$save_id = $this->orders_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('order_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('order_add_fail'), 400);
		}
		return $this->fail(app_lang('order_add_fail'), 400);
	}

	/**
	 * @api {put} api/orders/:id Update an Order
	 * @apiVersion 1.0.0
	 * @apiName updateOrder
	 * @apiGroup Orders
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Order unique ID
	 * @apiParam (body) {string} [order_date] Optional Order date to update
	 * @apiParam (body) {number} [status_id] Optional Status ID to update
	 * @apiParam (body) {string} [note] Optional Note to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Order update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_order_exists = $this->orders_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_order_exists)) {
			return $this->fail(app_lang('invalid_order_id'), 404);
		}

		$update_data = [
			'order_date' => $posted_data['order_date'] ?? $is_order_exists['order_date'],
			'status_id'  => $posted_data['status_id'] ?? $is_order_exists['status_id'],
			'note'       => $posted_data['note'] ?? $is_order_exists['note'],
		];

		$data = clean_data($update_data);
		if ($this->orders_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('order_update_success')]]);
		}
		return $this->fail(app_lang('order_update_fail'), 400);
	}

	/**
	 * @api {delete} api/orders/:id Delete an Order
	 * @apiVersion 1.0.0
	 * @apiName deleteOrder
	 * @apiGroup Orders
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Order delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->fail(app_lang('invalid_order_id'), 404);
		
		if ($this->orders_model->get_details(['id' => $id])->getResult()) {
			if ($this->orders_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('order_delete_success')]]);
			}
		}
		return $this->fail(app_lang('order_delete_fail'), 400);
	}
}
