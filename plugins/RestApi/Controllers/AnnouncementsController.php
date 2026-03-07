<?php

namespace RestApi\Controllers;

class AnnouncementsController extends Rest_api_Controller {
	protected $AnnouncementsModel = 'RestApi\Models\AnnouncementsModel';

	public function __construct() {
		parent::__construct();
		$this->announcements_model = model('App\Models\Announcements_model');
		$this->restapi_announcements_model = model($this->AnnouncementsModel);
	}

	/**
	 * @api {get} /api/announcements List all Announcements
	 * @apiVersion 1.0.0
	 * @apiName getAnnouncements
	 * @apiGroup Announcements
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data List of announcements
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Holiday notice",
	 *     "description": "Office closed",
	 *     "start_date": "2024-01-01",
	 *     "end_date": "2024-01-02"
	 *   }
	 * ]
	 */
	public function index() {
		$list_data = $this->announcements_model->get_details()->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/announcements/:id Get Announcement by ID
	 * @apiVersion 1.0.0
	 * @apiName showAnnouncement
	 * @apiGroup Announcements
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Announcement information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "title": "Holiday notice",
	 *   "description": "Office closed",
	 *   "start_date": "2024-01-01",
	 *   "end_date": "2024-01-02"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->announcements_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_announcement_id'));
	}

	/**
	 * @api {get} /api/announcements/search/:keyword Search Announcements
	 * @apiVersion 1.0.0
	 * @apiName searchAnnouncements
	 * @apiGroup Announcements
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Holiday notice"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_announcements_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/announcements Add New Announcement
	 * @apiVersion 1.0.0
	 * @apiName createAnnouncement
	 * @apiGroup Announcements
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} title Mandatory Announcement title
	 * @apiParam (body) {string} description Mandatory Announcement description
	 * @apiParam (body) {string} start_date Mandatory Start date (Y-m-d)
	 * @apiParam (body) {string} end_date Mandatory End date (Y-m-d)
	 * @apiParam (body) {number} [created_by] Optional RISE user ID. Default: 1. Use for proper attribution.
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created announcement ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Announcement add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = ['title' => 'required', 'description' => 'required', 'start_date' => 'required|valid_date[Y-m-d]', 'end_date' => 'required|valid_date[Y-m-d]'];
			if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

			$insert_data = [
				'title' => $posted_data['title'],
				'description' => $posted_data['description'],
				'start_date' => $posted_data['start_date'],
				'end_date' => $posted_data['end_date'],
				'created_by' => $this->getCreatedBy(),
				'created_at' => date('Y-m-d H:i:s'),
				'files' => serialize([])
			];

			$data = clean_data($insert_data);
			$save_id = $this->announcements_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('announcement_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('announcement_add_fail'), 400);
		}
		return $this->fail(app_lang('announcement_add_fail'), 400);
	}

	/**
	 * @api {put} api/announcements/:id Update an Announcement
	 * @apiVersion 1.0.0
	 * @apiName updateAnnouncement
	 * @apiGroup Announcements
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Announcement unique ID
	 * @apiParam (body) {string} [title] Optional Title to update
	 * @apiParam (body) {string} [description] Optional Description to update
	 * @apiParam (body) {string} [start_date] Optional Start date to update
	 * @apiParam (body) {string} [end_date] Optional End date to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Announcement update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_announcement_exists = $this->announcements_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_announcement_exists)) {
			return $this->fail(app_lang('invalid_announcement_id'), 404);
		}

		$update_data = [
			'title' => $posted_data['title'] ?? $is_announcement_exists['title'],
			'description' => $posted_data['description'] ?? $is_announcement_exists['description'],
			'start_date' => $posted_data['start_date'] ?? $is_announcement_exists['start_date'],
			'end_date' => $posted_data['end_date'] ?? $is_announcement_exists['end_date']
		];

		$data = clean_data($update_data);
		if ($this->announcements_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('announcement_update_success')]]);
		}
		return $this->fail(app_lang('announcement_update_fail'), 400);
	}

	/**
	 * @api {delete} api/announcements/:id Delete an Announcement
	 * @apiVersion 1.0.0
	 * @apiName deleteAnnouncement
	 * @apiGroup Announcements
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Announcement delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->fail(app_lang('invalid_announcement_id'), 404);
		
		if ($this->announcements_model->get_details(['id' => $id])->getResult()) {
			if ($this->announcements_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('announcement_delete_success')]]);
			}
		}
		return $this->fail(app_lang('announcement_delete_fail'), 400);
	}
}
