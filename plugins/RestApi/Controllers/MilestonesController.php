<?php

namespace RestApi\Controllers;

class MilestonesController extends Rest_api_Controller {
	protected $MilestonesModel = 'RestApi\Models\MilestonesModel';

	public function __construct() {
		parent::__construct();
		$this->milestones_model = model('App\Models\Milestones_model');
		$this->restapi_milestones_model = model($this->MilestonesModel);
		$this->projects_model = model('App\Models\Projects_model');
	}

	/**
	 * @api {get} /api/milestones List all Milestones
	 * @apiVersion 1.0.0
	 * @apiName getMilestones
	 * @apiGroup Milestones
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [project_id] Optional filter by project ID
	 *
	 * @apiSuccess {Object[]} data List of milestones
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Phase 1",
	 *     "project_id": "1",
	 *     "due_date": "2024-02-01",
	 *     "description": ""
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('project_id')) $options['project_id'] = $this->request->getGet('project_id');

		$list_data = $this->milestones_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/milestones/:id Get Milestone by ID
	 * @apiVersion 1.0.0
	 * @apiName showMilestone
	 * @apiGroup Milestones
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Milestone information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "title": "Phase 1",
	 *   "project_id": "1",
	 *   "due_date": "2024-02-01",
	 *   "description": ""
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->milestones_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_milestone_id'));
	}

	/**
	 * @api {get} /api/milestones/search/:keyword Search Milestones
	 * @apiVersion 1.0.0
	 * @apiName searchMilestones
	 * @apiGroup Milestones
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results (milestones)
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Phase 1"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_milestones_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/milestones Add New Milestone
	 * @apiVersion 1.0.0
	 * @apiName createMilestone
	 * @apiGroup Milestones
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} title Mandatory Milestone title
	 * @apiParam (body) {number} project_id Mandatory Project ID
	 * @apiParam (body) {string} due_date Mandatory Due date (Y-m-d)
	 * @apiParam (body) {string} [description] Optional Milestone description
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created milestone ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Milestone add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = [
				'title'      => 'required',
				'project_id' => 'required|numeric',
				'due_date'   => 'required|valid_date[Y-m-d]'
			];

			if (!$this->validate($rules)) {
				return $this->failValidationErrors($this->validator->getErrors());
			}

			$insert_data = [
				'title'       => $posted_data['title'],
				'project_id'  => $posted_data['project_id'],
				'due_date'    => $posted_data['due_date'],
				'description' => $posted_data['description'] ?? ''
			];

			$data = clean_data($insert_data);
			$save_id = $this->milestones_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('milestone_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('milestone_add_fail'), 400);
		}
		return $this->fail(app_lang('milestone_add_fail'), 400);
	}

	/**
	 * @api {put} api/milestones/:id Update a Milestone
	 * @apiVersion 1.0.0
	 * @apiName updateMilestone
	 * @apiGroup Milestones
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Milestone unique ID
	 * @apiParam (body) {string} [title] Optional Title to update
	 * @apiParam (body) {string} [due_date] Optional Due date to update
	 * @apiParam (body) {string} [description] Optional Description to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Milestone update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_milestone_exists = $this->milestones_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_milestone_exists)) {
			return $this->failNotFound(app_lang('invalid_milestone_id'));
		}

		$update_data = [
			'title'       => $posted_data['title'] ?? $is_milestone_exists['title'],
			'due_date'    => $posted_data['due_date'] ?? $is_milestone_exists['due_date'],
			'description' => $posted_data['description'] ?? $is_milestone_exists['description'],
		];

		$data = clean_data($update_data);
		if ($this->milestones_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('milestone_update_success')]]);
		}
		return $this->fail(app_lang('milestone_update_fail'), 400);
	}

	/**
	 * @api {delete} api/milestones/:id Delete a Milestone
	 * @apiVersion 1.0.0
	 * @apiName deleteMilestone
	 * @apiGroup Milestones
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Milestone delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->failNotFound(app_lang('invalid_milestone_id'));
		
		if ($this->milestones_model->get_details(['id' => $id])->getResult()) {
			if ($this->milestones_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('milestone_delete_success')]]);
			}
		}
		return $this->fail(app_lang('milestone_delete_fail'), 400);
	}
}
