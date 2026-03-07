<?php

namespace RestApi\Controllers;

class ActivityLogsController extends Rest_api_Controller {
	protected $ActivityLogsModel = 'RestApi\Models\ActivityLogsModel';

	public function __construct() {
		parent::__construct();
		$this->activity_logs_model = model('App\Models\Activity_logs_model');
		$this->restapi_activity_logs_model = model($this->ActivityLogsModel);
	}

	/**
	 * @api {get} /api/activity-logs List all Activity Logs (Read-Only)
	 * @apiVersion 1.0.0
	 * @apiName getActivityLogs
	 * @apiGroup ActivityLogs
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {String} [log_type] Optional filter by log type
	 * @apiParam (query) {String} [action] Optional filter by action (created, updated, deleted)
	 * @apiParam (query) {Number} [created_by] Optional filter by user ID
	 *
	 * @apiSuccess {Object[]} data List of activity log entries
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "log_type": "project",
	 *     "action": "created",
	 *     "created_by": "1",
	 *     "created_at": "2024-01-15 10:00:00"
	 *   }
	 * ]
	 * @apiDescription Read-only endpoint for audit trail viewing
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('log_type')) $options['log_type'] = $this->request->getGet('log_type');
		if ($this->request->getGet('action')) $options['action'] = $this->request->getGet('action');
		if ($this->request->getGet('created_by')) $options['created_by'] = $this->request->getGet('created_by');

		$list_data = $this->activity_logs_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/activity-logs/:id Get Activity Log by ID (Read-Only)
	 * @apiVersion 1.0.0
	 * @apiName showActivityLog
	 * @apiGroup ActivityLogs
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Activity log entry information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "log_type": "project",
	 *   "action": "created",
	 *   "created_by": "1",
	 *   "created_at": "2024-01-15 10:00:00"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->activity_logs_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_log_id'));
	}

	/**
	 * @api {get} /api/activity-logs/search/:keyword Search Activity Logs
	 * @apiVersion 1.0.0
	 * @apiName searchActivityLogs
	 * @apiGroup ActivityLogs
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results (activity log entries)
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "log_type": "project",
	 *     "action": "created"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_activity_logs_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	// Note: Activity Logs are typically read-only audit trails
	// Create, Update, Delete methods are intentionally not implemented
	// Logs are created automatically by the system
}
