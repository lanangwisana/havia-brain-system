<?php

namespace RestApi\Controllers;

class ContractsController extends Rest_api_Controller {
	protected $ContractsModel = 'RestApi\Models\ContractsModel';

	public function __construct() {
		parent::__construct();
		$this->contracts_model = model('App\Models\Contracts_model');
		$this->restapi_contracts_model = model($this->ContractsModel);
		$this->clients_model = model('App\Models\Clients_model');
	}

	/**
	 * @api {get} /api/contracts List all Contracts
	 * @apiVersion 1.0.0
	 * @apiName getContracts
	 * @apiGroup Contracts
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [client_id] Optional filter by client ID
	 * @apiParam (query) {String} [status] Optional filter by status (draft, sent, accepted, declined)
	 *
	 * @apiSuccess {Object[]} data List of contracts
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Contract A",
	 *     "client_id": "2",
	 *     "project_id": "1",
	 *     "contract_date": "2024-01-15",
	 *     "valid_until": "2024-12-31",
	 *     "status": "sent"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('client_id')) $options['client_id'] = $this->request->getGet('client_id');
		if ($this->request->getGet('status')) $options['status'] = $this->request->getGet('status');

		$list_data = $this->contracts_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/contracts/:id Get Contract by ID
	 * @apiVersion 1.0.0
	 * @apiName showContract
	 * @apiGroup Contracts
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Contract information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "title": "Contract A",
	 *   "client_id": "2",
	 *   "project_id": "1",
	 *   "contract_date": "2024-01-15",
	 *   "valid_until": "2024-12-31",
	 *   "status": "sent"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->contracts_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_contract_id'));
	}

	/**
	 * @api {get} /api/contracts/search/:keyword Search Contracts
	 * @apiVersion 1.0.0
	 * @apiName searchContracts
	 * @apiGroup Contracts
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results (contracts)
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Contract A"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_contracts_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/contracts Add New Contract
	 * @apiVersion 1.0.0
	 * @apiName createContract
	 * @apiGroup Contracts
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} title Mandatory Contract title
	 * @apiParam (body) {number} client_id Mandatory Client ID
	 * @apiParam (body) {number} project_id Mandatory Project ID
	 * @apiParam (body) {string} contract_date Mandatory Contract date (Y-m-d)
	 * @apiParam (body) {string} valid_until Mandatory Valid until date (Y-m-d)
	 * @apiParam (body) {string} [status] Optional Status (draft, sent, accepted, declined)
	 * @apiParam (body) {string} [content] Optional Contract content
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created contract ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Contract add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = [
				'title'         => 'required',
				'client_id'     => 'required|numeric',
				'project_id'    => 'required|numeric',
				'contract_date' => 'required|valid_date[Y-m-d]',
				'valid_until'   => 'required|valid_date[Y-m-d]'
			];

			if (!$this->validate($rules)) {
				return $this->failValidationErrors($this->validator->getErrors());
			}

			$insert_data = [
				'title'         => $posted_data['title'],
				'client_id'     => $posted_data['client_id'],
				'project_id'    => $posted_data['project_id'],
				'contract_date' => $posted_data['contract_date'],
				'valid_until'   => $posted_data['valid_until'],
				'status'        => $posted_data['status'] ?? 'draft',
				'note'          => $posted_data['note'] ?? '',
				'content'       => $posted_data['content'] ?? '',
				'discount_amount' => $posted_data['discount_amount'] ?? 0,
				'discount_amount_type' => 'percentage',
				'discount_type' => 'after_tax',
				'files' => serialize([])
			];

			$data = clean_data($insert_data);
			$save_id = $this->contracts_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('contract_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('contract_add_fail'), 400);
		}
		return $this->fail(app_lang('contract_add_fail'), 400);
	}

	/**
	 * @api {put} api/contracts/:id Update a Contract
	 * @apiVersion 1.0.0
	 * @apiName updateContract
	 * @apiGroup Contracts
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Contract unique ID
	 * @apiParam (body) {string} [title] Optional Title to update
	 * @apiParam (body) {string} [status] Optional Status to update
	 * @apiParam (body) {string} [note] Optional Note to update
	 * @apiParam (body) {string} [content] Optional Content to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Contract update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_contract_exists = $this->contracts_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_contract_exists)) {
			return $this->fail(app_lang('invalid_contract_id'), 404);
		}

		$update_data = [
			'title'  => $posted_data['title'] ?? $is_contract_exists['title'],
			'status' => $posted_data['status'] ?? $is_contract_exists['status'],
			'note'   => $posted_data['note'] ?? $is_contract_exists['note'],
			'content'=> $posted_data['content'] ?? $is_contract_exists['content'],
		];

		$data = clean_data($update_data);
		if ($this->contracts_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('contract_update_success')]]);
		}
		return $this->fail(app_lang('contract_update_fail'), 400);
	}

	/**
	 * @api {delete} api/contracts/:id Delete a Contract
	 * @apiVersion 1.0.0
	 * @apiName deleteContract
	 * @apiGroup Contracts
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Contract delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->fail(app_lang('invalid_contract_id'), 404);
		
		if ($this->contracts_model->get_details(['id' => $id])->getResult()) {
			if ($this->contracts_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('contract_delete_success')]]);
			}
		}
		return $this->fail(app_lang('contract_delete_fail'), 400);
	}
}
