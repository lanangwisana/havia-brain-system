<?php

namespace RestApi\Controllers;

class ToDoController extends Rest_api_Controller {
	protected $ToDoModel = 'RestApi\Models\ToDoModel';

	public function __construct() {
		parent::__construct();
		$this->to_do_model = model('App\Models\To_do_model');
		$this->restapi_to_do_model = model($this->ToDoModel);
	}

	/**
	 * @api {get} /api/todos List all ToDo Items
	 * @apiVersion 1.0.0
	 * @apiName getToDos
	 * @apiGroup ToDo
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {String} [status] Optional filter by status (to_do, done)
	 * @apiParam (query) {Number} [created_by] Optional filter by creator user ID
	 *
	 * @apiSuccess {Object[]} data List of ToDo items
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Task",
	 *     "description": "",
	 *     "status": "to_do",
	 *     "created_by": "1"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('status')) $options['status'] = $this->request->getGet('status');
		if ($this->request->getGet('created_by')) $options['created_by'] = $this->request->getGet('created_by');

		$list_data = $this->to_do_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/todos/:id Get ToDo by ID
	 * @apiVersion 1.0.0
	 * @apiName showToDo
	 * @apiGroup ToDo
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} ToDo item information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "title": "Task",
	 *   "description": "",
	 *   "status": "to_do"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->to_do_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_todo_id'));
	}

	/**
	 * @api {get} /api/todos/search/:keyword Search ToDo Items
	 * @apiVersion 1.0.0
	 * @apiName searchToDos
	 * @apiGroup ToDo
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Task"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_to_do_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/todos Add New ToDo Item
	 * @apiVersion 1.0.0
	 * @apiName createToDo
	 * @apiGroup ToDo
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} title Mandatory ToDo title
	 * @apiParam (body) {string} [description] Optional ToDo description
	 * @apiParam (body) {string} [status] Optional Status (to_do, done) default: to_do
	 * @apiParam (body) {string} [start_date] Optional Start date (Y-m-d)
	 * @apiParam (body) {number} [created_by] Optional RISE user ID. Default: 1. Use for proper attribution.
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created ToDo ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "ToDo add success"},
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
				'status' => $posted_data['status'] ?? 'to_do',
				'start_date' => $posted_data['start_date'] ?? null,
				'created_by' => $this->getCreatedBy(),
				'created_at' => date('Y-m-d H:i:s'),
				'files' => serialize([])
			];

			$data = clean_data($insert_data);
			$save_id = $this->to_do_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('todo_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('todo_add_fail'), 400);
		}
		return $this->fail(app_lang('todo_add_fail'), 400);
	}

	/**
	 * @api {put} api/todos/:id Update a ToDo Item
	 * @apiVersion 1.0.0
	 * @apiName updateToDo
	 * @apiGroup ToDo
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id ToDo unique ID
	 * @apiParam (body) {string} [title] Optional Title to update
	 * @apiParam (body) {string} [description] Optional Description to update
	 * @apiParam (body) {string} [status] Optional Status (to_do, done) to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "ToDo update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_todo_exists = $this->to_do_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_todo_exists)) {
			return $this->failNotFound(app_lang('invalid_todo_id'));
		}

		$update_data = [
			'title' => $posted_data['title'] ?? $is_todo_exists['title'],
			'description' => $posted_data['description'] ?? $is_todo_exists['description'],
			'status' => $posted_data['status'] ?? $is_todo_exists['status']
		];

		$data = clean_data($update_data);
		if ($this->to_do_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('todo_update_success')]]);
		}
		return $this->fail(app_lang('todo_update_fail'), 400);
	}

	/**
	 * @api {delete} api/todos/:id Delete a ToDo Item
	 * @apiVersion 1.0.0
	 * @apiName deleteToDo
	 * @apiGroup ToDo
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "ToDo delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->failNotFound(app_lang('invalid_todo_id'));
		
		if ($this->to_do_model->get_details(['id' => $id])->getResult()) {
			if ($this->to_do_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('todo_delete_success')]]);
			}
		}
		return $this->fail(app_lang('todo_delete_fail'), 400);
	}
}
