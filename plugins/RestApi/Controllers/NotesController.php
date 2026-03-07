<?php

namespace RestApi\Controllers;

class NotesController extends Rest_api_Controller {
	protected $NotesModel = 'RestApi\Models\NotesModel';

	public function __construct() {
		parent::__construct();
		$this->notes_model = model('App\Models\Notes_model');
		$this->restapi_notes_model = model($this->NotesModel);
	}

	/**
	 * @api {get} /api/notes List all Notes
	 * @apiVersion 1.0.0
	 * @apiName getNotes
	 * @apiGroup Notes
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [project_id] Optional filter by project ID
	 * @apiParam (query) {Number} [client_id] Optional filter by client ID
	 * @apiParam (query) {Number} [is_public] Optional filter by public/private (0 or 1)
	 *
	 * @apiSuccess {Object[]} data List of notes
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Meeting notes",
	 *     "description": "Summary",
	 *     "project_id": "1",
	 *     "client_id": "0",
	 *     "is_public": "0"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('project_id')) $options['project_id'] = $this->request->getGet('project_id');
		if ($this->request->getGet('client_id')) $options['client_id'] = $this->request->getGet('client_id');
		if ($this->request->getGet('is_public')) $options['is_public'] = $this->request->getGet('is_public');

		$list_data = $this->notes_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/notes/:id Get Note by ID
	 * @apiVersion 1.0.0
	 * @apiName showNote
	 * @apiGroup Notes
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Note information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "title": "Meeting notes",
	 *   "description": "Summary",
	 *   "project_id": "1",
	 *   "client_id": "0",
	 *   "is_public": "0"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->notes_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_note_id'));
	}

	/**
	 * @api {get} /api/notes/search/:keyword Search Notes
	 * @apiVersion 1.0.0
	 * @apiName searchNotes
	 * @apiGroup Notes
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results (notes)
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Meeting notes"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_notes_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/notes Add New Note
	 * @apiVersion 1.0.0
	 * @apiName createNote
	 * @apiGroup Notes
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} title Mandatory Note title
	 * @apiParam (body) {string} [description] Optional Note description
	 * @apiParam (body) {number} [project_id] Optional Project ID
	 * @apiParam (body) {number} [client_id] Optional Client ID
	 * @apiParam (body) {number} [is_public] Optional Public/Private (0 or 1) default: 0
	 * @apiParam (body) {number} [created_by] Optional RISE user ID. Default: 1. Use for proper attribution.
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created note ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Note add success"},
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
				'project_id' => $posted_data['project_id'] ?? 0,
				'client_id' => $posted_data['client_id'] ?? 0,
				'is_public' => $posted_data['is_public'] ?? 0,
				'created_by' => $this->getCreatedBy(),
				'created_at' => date('Y-m-d H:i:s'),
				'files' => serialize([])
			];

			$data = clean_data($insert_data);
			$save_id = $this->notes_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('note_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('note_add_fail'), 400);
		}
		return $this->fail(app_lang('note_add_fail'), 400);
	}

	/**
	 * @api {put} api/notes/:id Update a Note
	 * @apiVersion 1.0.0
	 * @apiName updateNote
	 * @apiGroup Notes
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Note unique ID
	 * @apiParam (body) {string} [title] Optional Title to update
	 * @apiParam (body) {string} [description] Optional Description to update
	 * @apiParam (body) {number} [is_public] Optional Public/Private to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Note update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_note_exists = $this->notes_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_note_exists)) {
			return $this->failNotFound(app_lang('invalid_note_id'));
		}

		$update_data = [
			'title' => $posted_data['title'] ?? $is_note_exists['title'],
			'description' => $posted_data['description'] ?? $is_note_exists['description'],
			'is_public' => $posted_data['is_public'] ?? $is_note_exists['is_public']
		];

		$data = clean_data($update_data);
		if ($this->notes_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('note_update_success')]]);
		}
		return $this->fail(app_lang('note_update_fail'), 400);
	}

	/**
	 * @api {delete} api/notes/:id Delete a Note
	 * @apiVersion 1.0.0
	 * @apiName deleteNote
	 * @apiGroup Notes
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Note delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->failNotFound(app_lang('invalid_note_id'));
		
		if ($this->notes_model->get_details(['id' => $id])->getResult()) {
			if ($this->notes_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('note_delete_success')]]);
			}
		}
		return $this->fail(app_lang('note_delete_fail'), 400);
	}
}
