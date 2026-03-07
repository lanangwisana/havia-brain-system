<?php

namespace RestApi\Controllers;

class LeaveApplicationsController extends Rest_api_Controller {
	protected $LeaveApplicationsModel = 'RestApi\Models\LeaveApplicationsModel';

	public function __construct() {
		parent::__construct();
		$this->leave_applications_model = model('App\Models\Leave_applications_model');
		$this->restapi_leave_applications_model = model($this->LeaveApplicationsModel);
		$this->users_model = model('App\Models\Users_model');
		$this->leave_types_model = model('App\Models\Leave_types_model');
	}

	/**
	 * @api {get} /api/leave-applications List all Leave Applications
	 * @apiVersion 1.0.0
	 * @apiName getLeaveApplications
	 * @apiGroup LeaveApplications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [applicant_id] Optional filter by applicant user ID
	 * @apiParam (query) {String} [status] Optional filter by status (pending, approved, rejected, canceled)
	 *
	 * @apiSuccess {Object[]} data List of leave applications
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "leave_type_id": "1",
	 *     "applicant_id": "1",
	 *     "start_date": "2024-01-15",
	 *     "end_date": "2024-01-17",
	 *     "reason": "Personal",
	 *     "status": "pending"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('applicant_id')) $options['applicant_id'] = $this->request->getGet('applicant_id');
		if ($this->request->getGet('status')) $options['status'] = $this->request->getGet('status');

		$list_data = $this->leave_applications_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/leave-applications/:id Get Leave Application by ID
	 * @apiVersion 1.0.0
	 * @apiName showLeaveApplication
	 * @apiGroup LeaveApplications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Leave application information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "leave_type_id": "1",
	 *   "applicant_id": "1",
	 *   "start_date": "2024-01-15",
	 *   "end_date": "2024-01-17",
	 *   "reason": "Personal",
	 *   "status": "pending"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->leave_applications_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_leave_application_id'));
	}

	/**
	 * @api {get} /api/leave-applications/search/:keyword Search Leave Applications
	 * @apiVersion 1.0.0
	 * @apiName searchLeaveApplications
	 * @apiGroup LeaveApplications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "reason": "Personal leave"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_leave_applications_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/leave-applications Add Leave Application
	 * @apiVersion 1.0.0
	 * @apiName createLeaveApplication
	 * @apiGroup LeaveApplications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {number} leave_type_id Mandatory Leave type ID
	 * @apiParam (body) {number} applicant_id Mandatory Applicant user ID
	 * @apiParam (body) {string} start_date Mandatory Start date (Y-m-d)
	 * @apiParam (body) {string} end_date Mandatory End date (Y-m-d)
	 * @apiParam (body) {string} reason Mandatory Leave reason
	 * @apiParam (body) {number} [total_days] Optional Total days (auto-calculated if not provided)
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created leave application ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Leave application add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = ['leave_type_id' => 'required|numeric', 'applicant_id' => 'required|numeric', 'start_date' => 'required|valid_date[Y-m-d]', 'end_date' => 'required|valid_date[Y-m-d]', 'reason' => 'required'];
			if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

			$insert_data = [
				'leave_type_id' => $posted_data['leave_type_id'],
				'applicant_id' => $posted_data['applicant_id'],
				'start_date' => $posted_data['start_date'],
				'end_date' => $posted_data['end_date'],
				'reason' => $posted_data['reason'],
				'total_days' => $posted_data['total_days'] ?? 1,
				'total_hours' => $posted_data['total_hours'] ?? 8,
				'status' => 'pending',
				'created_by' => $this->getCreatedBy(),
				'created_at' => date('Y-m-d H:i:s'),
				'files' => serialize([])
			];

			$data = clean_data($insert_data);
			$save_id = $this->leave_applications_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('leave_application_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('leave_application_add_fail'), 400);
		}
		return $this->fail(app_lang('leave_application_add_fail'), 400);
	}

	/**
	 * @api {put} api/leave-applications/:id Update Leave Application
	 * @apiVersion 1.0.0
	 * @apiName updateLeaveApplication
	 * @apiGroup LeaveApplications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Leave application unique ID
	 * @apiParam (body) {string} [status] Optional Status (pending, approved, rejected, canceled) to update
	 * @apiParam (body) {string} [reason] Optional Reason to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Leave application update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_leave_exists = $this->leave_applications_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_leave_exists)) {
			return $this->failNotFound(app_lang('invalid_leave_application_id'));
		}

		$update_data = [
			'status' => $posted_data['status'] ?? $is_leave_exists['status'],
			'reason' => $posted_data['reason'] ?? $is_leave_exists['reason']
		];

		$data = clean_data($update_data);
		if ($this->leave_applications_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('leave_application_update_success')]]);
		}
		return $this->fail(app_lang('leave_application_update_fail'), 400);
	}

	/**
	 * @api {delete} api/leave-applications/:id Delete Leave Application
	 * @apiVersion 1.0.0
	 * @apiName deleteLeaveApplication
	 * @apiGroup LeaveApplications
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Leave application delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->failNotFound(app_lang('invalid_leave_application_id'));
		
		if ($this->leave_applications_model->get_details(['id' => $id])->getResult()) {
			if ($this->leave_applications_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('leave_application_delete_success')]]);
			}
		}
		return $this->fail(app_lang('leave_application_delete_fail'), 400);
	}
}
