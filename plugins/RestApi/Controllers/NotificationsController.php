<?php

namespace RestApi\Controllers;

class NotificationsController extends Rest_api_Controller {
	protected $NotificationsModel = 'RestApi\Models\NotificationsModel';

	public function __construct() {
		parent::__construct();
		$this->notifications_model = model('App\Models\Notifications_model');
		$this->restapi_notifications_model = model($this->NotificationsModel);
	}

	/**
	 * @api {get} /api/notifications List all Notifications
	 * @apiVersion 1.0.0
	 * @apiName getNotifications
	 * @apiGroup Notifications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [user_id] Optional filter by user ID
	 * @apiParam (query) {String} [status] Optional filter by status (read, unread)
	 *
	 * @apiSuccess {Object[]} data List of notifications
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "user_id": "1",
	 *     "event": "task_assigned",
	 *     "title": "New task",
	 *     "is_read": "0"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('user_id')) $options['user_id'] = $this->request->getGet('user_id');
		if ($this->request->getGet('status')) $options['status'] = $this->request->getGet('status');

		$list_data = $this->notifications_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/notifications/:id Get Notification by ID
	 * @apiVersion 1.0.0
	 * @apiName showNotification
	 * @apiGroup Notifications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Notification information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "user_id": "1",
	 *   "event": "task_assigned",
	 *   "title": "New task",
	 *   "is_read": "0"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->notifications_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_notification_id'));
	}

	/**
	 * @api {get} /api/notifications/search/:keyword Search Notifications
	 * @apiVersion 1.0.0
	 * @apiName searchNotifications
	 * @apiGroup Notifications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "New task"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_notifications_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/notifications Add Notification
	 * @apiVersion 1.0.0
	 * @apiName createNotification
	 * @apiGroup Notifications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {number} user_id Mandatory User ID
	 * @apiParam (body) {string} event Mandatory Event name
	 * @apiParam (body) {string} [title] Optional Notification title
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created notification ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Notification add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = ['user_id' => 'required|numeric', 'event' => 'required'];
			if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

			$insert_data = [
				'user_id' => $posted_data['user_id'],
				'event' => $posted_data['event'],
				'title' => $posted_data['title'] ?? '',
				'created_at' => date('Y-m-d H:i:s')
			];

			$data = clean_data($insert_data);
			$save_id = $this->notifications_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('notification_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('notification_add_fail'), 400);
		}
		return $this->fail(app_lang('notification_add_fail'), 400);
	}

	/**
	 * @api {put} api/notifications/:id Update Notification (Mark as Read)
	 * @apiVersion 1.0.0
	 * @apiName updateNotification
	 * @apiGroup Notifications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Notification unique ID
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Notification update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_notification_exists = $this->notifications_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_notification_exists)) {
			return $this->fail(app_lang('invalid_notification_id'), 404);
		}

		$update_data = ['is_read' => 1];
		$data = clean_data($update_data);

		if ($this->notifications_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('notification_update_success')]]);
		}
		return $this->fail(app_lang('notification_update_fail'), 400);
	}

	/**
	 * @api {delete} api/notifications/:id Delete Notification
	 * @apiVersion 1.0.0
	 * @apiName deleteNotification
	 * @apiGroup Notifications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Notification delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->fail(app_lang('invalid_notification_id'), 404);
		
		if ($this->notifications_model->get_details(['id' => $id])->getResult()) {
			if ($this->notifications_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('notification_delete_success')]]);
			}
		}
		return $this->fail(app_lang('notification_delete_fail'), 400);
	}
}
