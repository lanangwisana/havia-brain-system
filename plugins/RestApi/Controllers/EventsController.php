<?php

namespace RestApi\Controllers;

class EventsController extends Rest_api_Controller {
	protected $EventsModel = 'RestApi\Models\EventsModel';

	public function __construct() {
		parent::__construct();
		$this->events_model = model('App\Models\Events_model');
		$this->restapi_events_model = model($this->EventsModel);
	}

	/**
	 * @api {get} /api/events List all Events
	 * @apiVersion 1.0.0
	 * @apiName getEvents
	 * @apiGroup Events
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {String} [type] Optional filter by type (event, reminder)
	 * @apiParam (query) {Number} [project_id] Optional filter by project ID
	 *
	 * @apiSuccess {Object[]} data List of events
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Team meeting",
	 *     "start_date": "2024-01-15",
	 *     "end_date": null,
	 *     "type": "event"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('type')) $options['type'] = $this->request->getGet('type');
		if ($this->request->getGet('project_id')) $options['project_id'] = $this->request->getGet('project_id');

		$list_data = $this->events_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/events/:id Get Event by ID
	 * @apiVersion 1.0.0
	 * @apiName showEvent
	 * @apiGroup Events
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Event information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "title": "Team meeting",
	 *   "start_date": "2024-01-15",
	 *   "end_date": null,
	 *   "type": "event"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->events_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_event_id'));
	}

	/**
	 * @api {get} /api/events/search/:keyword Search Events
	 * @apiVersion 1.0.0
	 * @apiName searchEvents
	 * @apiGroup Events
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results (events)
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Team meeting"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_events_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/events Add New Event
	 * @apiVersion 1.0.0
	 * @apiName createEvent
	 * @apiGroup Events
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} title Mandatory Event title
	 * @apiParam (body) {string} start_date Mandatory Start date (Y-m-d)
	 * @apiParam (body) {string} [end_date] Optional End date (Y-m-d)
	 * @apiParam (body) {string} [description] Optional Event description
	 * @apiParam (body) {string} [location] Optional Event location
	 * @apiParam (body) {number} [created_by] Optional RISE user ID. Default: 1. Use for proper attribution.
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created event ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Event add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = ['title' => 'required', 'start_date' => 'required|valid_date[Y-m-d]'];
			if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

			$insert_data = [
				'title' => $posted_data['title'],
				'description' => $posted_data['description'] ?? '',
				'start_date' => $posted_data['start_date'],
				'end_date' => $posted_data['end_date'] ?? null,
				'start_time' => $posted_data['start_time'] ?? null,
				'end_time' => $posted_data['end_time'] ?? null,
				'location' => $posted_data['location'] ?? '',
				'type' => 'event',
				'created_by' => $this->getCreatedBy(),
				'files' => serialize([])
			];

			$data = clean_data($insert_data);
			$save_id = $this->events_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('event_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('event_add_fail'), 400);
		}
		return $this->fail(app_lang('event_add_fail'), 400);
	}

	/**
	 * @api {put} api/events/:id Update an Event
	 * @apiVersion 1.0.0
	 * @apiName updateEvent
	 * @apiGroup Events
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Event unique ID
	 * @apiParam (body) {string} [title] Optional Title to update
	 * @apiParam (body) {string} [description] Optional Description to update
	 * @apiParam (body) {string} [start_date] Optional Start date to update
	 * @apiParam (body) {string} [end_date] Optional End date to update
	 * @apiParam (body) {string} [location] Optional Location to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Event update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_event_exists = $this->events_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_event_exists)) {
			return $this->fail(app_lang('invalid_event_id'), 404);
		}

		$update_data = [
			'title' => $posted_data['title'] ?? $is_event_exists['title'],
			'description' => $posted_data['description'] ?? $is_event_exists['description'],
			'start_date' => $posted_data['start_date'] ?? $is_event_exists['start_date'],
			'end_date' => $posted_data['end_date'] ?? $is_event_exists['end_date'],
			'location' => $posted_data['location'] ?? $is_event_exists['location']
		];

		$data = clean_data($update_data);
		if ($this->events_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('event_update_success')]]);
		}
		return $this->fail(app_lang('event_update_fail'), 400);
	}

	/**
	 * @api {delete} api/events/:id Delete an Event
	 * @apiVersion 1.0.0
	 * @apiName deleteEvent
	 * @apiGroup Events
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Event delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->fail(app_lang('invalid_event_id'), 404);
		
		if ($this->events_model->get_details(['id' => $id])->getResult()) {
			if ($this->events_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('event_delete_success')]]);
			}
		}
		return $this->fail(app_lang('event_delete_fail'), 400);
	}
}
