<?php

namespace RestApi\Controllers;

class AttendanceController extends Rest_api_Controller {
	protected $AttendanceModel = 'RestApi\Models\AttendanceModel';

	public function __construct() {
		parent::__construct();
		$this->attendance_model = model('App\Models\Attendance_model');
		$this->restapi_attendance_model = model($this->AttendanceModel);
		$this->users_model = model('App\Models\Users_model');
	}

	/**
	 * @api {get} /api/attendance List all Attendance Records
	 * @apiVersion 1.0.0
	 * @apiName getAttendance
	 * @apiGroup Attendance
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [user_id] Optional filter by user ID
	 * @apiParam (query) {String} [status] Optional filter by status (pending, approved, rejected)
	 *
	 * @apiSuccess {Object[]} data List of attendance records
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "user_id": "1",
	 *     "in_time": "2024-01-15 09:00:00",
	 *     "out_time": null,
	 *     "status": "pending"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('user_id')) $options['user_id'] = $this->request->getGet('user_id');
		if ($this->request->getGet('status')) $options['status'] = $this->request->getGet('status');

		$list_data = $this->attendance_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/attendance/:id Get Attendance by ID
	 * @apiVersion 1.0.0
	 * @apiName showAttendance
	 * @apiGroup Attendance
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Attendance record information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "user_id": "1",
	 *   "in_time": "2024-01-15 09:00:00",
	 *   "out_time": null,
	 *   "status": "pending"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->attendance_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_attendance_id'));
	}

	/**
	 * @api {get} /api/attendance/search/:keyword Search Attendance
	 * @apiVersion 1.0.0
	 * @apiName searchAttendance
	 * @apiGroup Attendance
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "user_id": "1"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_attendance_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/attendance Add Attendance Record
	 * @apiVersion 1.0.0
	 * @apiName createAttendance
	 * @apiGroup Attendance
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {number} user_id Mandatory User ID
	 * @apiParam (body) {string} in_time Mandatory Clock-in time (Y-m-d H:i:s)
	 * @apiParam (body) {string} [out_time] Optional Clock-out time (Y-m-d H:i:s)
	 * @apiParam (body) {string} [note] Optional Note
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created attendance record ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Attendance add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = ['user_id' => 'required|numeric', 'in_time' => 'required'];
			if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

			$insert_data = [
				'user_id' => $posted_data['user_id'],
				'in_time' => $posted_data['in_time'],
				'out_time' => $posted_data['out_time'] ?? null,
				'note' => $posted_data['note'] ?? '',
				'status' => 'pending'
			];

			$data = clean_data($insert_data);
			$save_id = $this->attendance_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('attendance_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('attendance_add_fail'), 400);
		}
		return $this->fail(app_lang('attendance_add_fail'), 400);
	}

	/**
	 * @api {put} api/attendance/:id Update Attendance
	 * @apiVersion 1.0.0
	 * @apiName updateAttendance
	 * @apiGroup Attendance
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Attendance record unique ID
	 * @apiParam (body) {string} [out_time] Optional Clock-out time
	 * @apiParam (body) {string} [status] Optional Status (pending, approved, rejected)
	 * @apiParam (body) {string} [note] Optional Note
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Attendance update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_attendance_exists = $this->attendance_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_attendance_exists)) {
			return $this->fail(app_lang('invalid_attendance_id'), 404);
		}

		$update_data = [
			'out_time' => $posted_data['out_time'] ?? $is_attendance_exists['out_time'],
			'status' => $posted_data['status'] ?? $is_attendance_exists['status'],
			'note' => $posted_data['note'] ?? $is_attendance_exists['note']
		];

		$data = clean_data($update_data);
		if ($this->attendance_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('attendance_update_success')]]);
		}
		return $this->fail(app_lang('attendance_update_fail'), 400);
	}

	/**
	 * @api {delete} api/attendance/:id Delete Attendance
	 * @apiVersion 1.0.0
	 * @apiName deleteAttendance
	 * @apiGroup Attendance
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Attendance delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->fail(app_lang('invalid_attendance_id'), 404);
		
		if ($this->attendance_model->get_details(['id' => $id])->getResult()) {
			if ($this->attendance_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('attendance_delete_success')]]);
			}
		}
		return $this->fail(app_lang('attendance_delete_fail'), 400);
	}
}
