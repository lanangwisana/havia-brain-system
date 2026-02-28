<?php

namespace RestApi\Controllers;

class TasksController extends Rest_api_Controller {
	protected $TasksModel = 'RestApi\Models\TasksModel';

	public function __construct() {
		parent::__construct();

		$this->tasks_model = model('App\Models\Tasks_model');
		$this->restapi_tasks_model = model($this->TasksModel);
		$this->projects_model = model('App\Models\Projects_model');
		$this->users_model = model('App\Models\Users_model');
		$this->task_status_model = model('App\Models\Task_status_model');
		$this->task_priority_model = model('App\Models\Task_priority_model');
		$this->milestones_model = model('App\Models\Milestones_model');
	}

	/**
	 * @api {get} /api/tasks List all Tasks
	 * @apiVersion 1.0.0
	 * @apiName getTasks
	 * @apiGroup Tasks
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [project_id] Optional filter by project ID
	 * @apiParam (query) {String} [status] Optional filter by status (to_do, in_progress, done)
	 * @apiParam (query) {Number} [assigned_to] Optional filter by assigned user ID
	 *
	 * @apiSuccess {Object} Tasks information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"title": "Task Title",
	 * 		"description": "Task description",
	 * 		"project_id": "1",
	 * 		"milestone_id": "0",
	 * 		"assigned_to": "2",
	 * 		"deadline": "2021-12-31 23:59:59",
	 * 		"status": "in_progress",
	 * 		"priority_id": "1",
	 * 		"start_date": "2021-09-01 00:00:00",
	 * 		"created_date": "2021-09-01",
	 * 		"context": "project"
	 * }
	 *
	 */
	public function index() {
		$options = [];
		
		if ($this->request->getGet('project_id')) {
			$options['project_id'] = $this->request->getGet('project_id');
		}
		
		if ($this->request->getGet('status')) {
			$options['status'] = $this->request->getGet('status');
		}
		
		if ($this->request->getGet('assigned_to')) {
			$options['assigned_to'] = $this->request->getGet('assigned_to');
		}

		$list_data = $this->tasks_model->get_details($options)->getResult();
		
		if (empty($list_data)) {
			return $this->failNotFound(app_lang('no_data_were_found'));
		}

		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/tasks/:id Get Task by ID
	 * @apiVersion 1.0.0
	 * @apiName showTask
	 * @apiGroup Tasks
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam {Number} id Task unique ID
	 *
	 * @apiSuccess {Object} Task information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"title": "Task Title",
	 * 		"description": "Task description",
	 * 		"project_id": "1",
	 * 		"milestone_id": "0",
	 * 		"assigned_to": "2",
	 * 		"deadline": "2021-12-31 23:59:59",
	 * 		"status": "in_progress",
	 * 		"priority_id": "1",
	 * 		"start_date": "2021-09-01 00:00:00",
	 * 		"created_date": "2021-09-01",
	 * 		"context": "project"
	 * }
	 *
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->tasks_model->get_details(['id' => $id])->getRow();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('invalid_task_id'));
	}

	/**
	 * @api {get} /api/tasks/search/:keyword Search Tasks
	 * @apiVersion 1.0.0
	 * @apiName searchTasks
	 * @apiGroup Tasks
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {String} keyword Search keyword
	 *
	 * @apiSuccess {Object} Tasks information
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "title": "Task Title",
	 *     "project_id": "1",
	 *     "assigned_to": "2",
	 *     "status": "in_progress",
	 *     "priority_id": "1"
	 *   }
	 * ]
	 *
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_tasks_model->get_search_suggestion($key)->getResult();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/tasks Add New Task
	 * @apiVersion 1.0.0
	 * @apiName createTask
	 * @apiGroup Tasks
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} title Mandatory Task title
	 * @apiParam (body) {number} project_id Mandatory Project ID
	 * @apiParam (body) {number} assigned_to Mandatory Assigned user ID
	 * @apiParam (body) {number} status_id Mandatory Task status ID
	 * @apiParam (body) {number} priority_id Mandatory Task priority ID
	 * @apiParam (body) {string} [description] Optional Task description
	 * @apiParam (body) {number} [milestone_id] Optional Milestone ID
	 * @apiParam (body) {string} [deadline] Optional Deadline (Y-m-d H:i:s)
	 * @apiParam (body) {string} [start_date] Optional Start date (Y-m-d H:i:s)
	 * @apiParam (body) {string} [labels] Optional Comma-separated label IDs
	 * @apiParam (body) {number} [points] Optional Story points (default: 1)
	 * @apiParam (body) {string} [collaborators] Optional Comma-separated user IDs
	 *
	 * @apiParamExample Request-Example:
	 *     {
	 *        "title": "New Task",
	 *        "description": "Task description here",
	 *        "project_id": 1,
	 *        "assigned_to": 2,
	 *        "status_id": 1,
	 *        "priority_id": 1,
	 *        "deadline": "2021-12-31 23:59:59",
	 *        "start_date": "2021-09-01 00:00:00",
	 *        "points": 3
	 *     }
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Task add successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Task add successful."
	 *     }
	 *
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		
		if (!empty($posted_data)) {
			$rules = [
				'title'       => 'required',
				'project_id'  => 'required|numeric',
				'assigned_to' => 'required|numeric',
				'status_id'   => 'required|numeric',
				'priority_id' => 'required|numeric',
				'milestone_id'=> 'numeric|if_exist',
				'points'      => 'numeric|if_exist',
				'deadline'    => 'valid_date[Y-m-d H:i:s]|if_exist',
				'start_date'  => 'valid_date[Y-m-d H:i:s]|if_exist'
			];

			$error = [
				'title' => [
					'required' => app_lang('task_title_required')
				],
				'project_id' => [
					'required' => app_lang('project_id_required'),
					'numeric'  => app_lang('invalid_project_id')
				],
				'assigned_to' => [
					'required' => app_lang('assigned_to_required'),
					'numeric'  => app_lang('invalid_assigned_to')
				],
				'status_id' => [
					'required' => app_lang('status_id_required'),
					'numeric'  => app_lang('invalid_status_id')
				],
				'priority_id' => [
					'required' => app_lang('priority_id_required'),
					'numeric'  => app_lang('invalid_priority_id')
				],
				'milestone_id' => [
					'numeric' => app_lang('invalid_milestone_id')
				],
				'points' => [
					'numeric' => app_lang('invalid_points')
				],
				'deadline' => [
					'valid_date' => app_lang('invalid_deadline')
				],
				'start_date' => [
					'valid_date' => app_lang('invalid_start_date')
				]
			];

			if (!$this->validate($rules, $error)) {
				return $this->failValidationErrors($this->validator->getErrors());
			}

			// Validate project exists
			$is_project_exists = $this->projects_model->get_details(['id' => $posted_data['project_id']])->getResult();
			if (empty($is_project_exists)) {
				return $this->failValidationError(app_lang('invalid_project_id'));
			}

			// Validate assigned user exists
			$is_user_exists = $this->users_model->get_details(['id' => $posted_data['assigned_to']])->getResult();
			if (empty($is_user_exists)) {
				return $this->failValidationError(app_lang('invalid_assigned_to'));
			}

			// Validate status exists
			$is_status_exists = $this->task_status_model->get_details(['id' => $posted_data['status_id']])->getResult();
			if (empty($is_status_exists)) {
				return $this->failValidationError(app_lang('invalid_status_id'));
			}

			// Validate priority exists
			$is_priority_exists = $this->task_priority_model->get_details(['id' => $posted_data['priority_id']])->getResult();
			if (empty($is_priority_exists)) {
				return $this->failValidationError(app_lang('invalid_priority_id'));
			}

			// Validate milestone if provided
			if (isset($posted_data['milestone_id']) && $posted_data['milestone_id'] > 0) {
				$is_milestone_exists = $this->milestones_model->get_details(['id' => $posted_data['milestone_id']])->getResult();
				if (empty($is_milestone_exists)) {
					return $this->failValidationError(app_lang('invalid_milestone_id'));
				}
			}

			$insert_data = [
				'title'         => $posted_data['title'],
				'description'   => $posted_data['description'] ?? '',
				'project_id'    => $posted_data['project_id'],
				'assigned_to'   => $posted_data['assigned_to'],
				'status_id'     => $posted_data['status_id'],
				'priority_id'   => $posted_data['priority_id'],
				'milestone_id'  => $posted_data['milestone_id'] ?? 0,
				'deadline'      => $posted_data['deadline'] ?? null,
				'start_date'    => $posted_data['start_date'] ?? null,
				'labels'        => $posted_data['labels'] ?? '',
				'points'        => $posted_data['points'] ?? 1,
				'collaborators' => $posted_data['collaborators'] ?? '',
				'created_date'  => date('Y-m-d'),
				'context'       => 'project'
			];

			$data = clean_data($insert_data);

			$save_id = $this->tasks_model->ci_save($data);
			
			if ($save_id > 0 && !empty($save_id)) {
				$response = [
					'status'   => 200,
					'messages' => [
						'success' => app_lang('task_add_success')
					],
					'id' => $save_id
				];
				return $this->respondCreated($response);
			}
			
			return $this->fail(app_lang('task_add_fail'), 400);
		}
		
		return $this->fail(app_lang('task_add_fail'), 400);
	}

	/**
	 * @api {put} api/tasks/:id Update a Task
	 * @apiVersion 1.0.0
	 * @apiName updateTask
	 * @apiGroup Tasks
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Task unique ID
	 * @apiParam (body) {string} [title] Optional Task title
	 * @apiParam (body) {string} [description] Optional Task description
	 * @apiParam (body) {number} [assigned_to] Optional Assigned user ID
	 * @apiParam (body) {number} [status_id] Optional Task status ID
	 * @apiParam (body) {number} [priority_id] Optional Task priority ID
	 * @apiParam (body) {string} [deadline] Optional Deadline
	 * @apiParam (body) {number} [points] Optional Story points
	 *
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	    "title": "Updated Task Title",
	 *	    "description": "Updated description",
	 *	    "status_id": 2,
	 *	    "priority_id": 2,
	 *	    "deadline": "2021-12-31 23:59:59"
	 *	}
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Task Update Successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Task Update Successful."
	 *     }
	 *
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_task_exists = $this->tasks_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_task_exists)) {
			return $this->fail(app_lang('invalid_task_id'), 404);
		}

		$rules = [
			'title'       => 'required|if_exist',
			'assigned_to' => 'numeric|if_exist',
			'status_id'   => 'numeric|if_exist',
			'priority_id' => 'numeric|if_exist',
			'milestone_id'=> 'numeric|if_exist',
			'points'      => 'numeric|if_exist',
			'deadline'    => 'valid_date[Y-m-d H:i:s]|if_exist',
			'start_date'  => 'valid_date[Y-m-d H:i:s]|if_exist'
		];

		$error = [
			'title' => [
				'required' => app_lang('task_title_required')
			],
			'assigned_to' => [
				'numeric' => app_lang('invalid_assigned_to')
			],
			'status_id' => [
				'numeric' => app_lang('invalid_status_id')
			],
			'priority_id' => [
				'numeric' => app_lang('invalid_priority_id')
			],
			'milestone_id' => [
				'numeric' => app_lang('invalid_milestone_id')
			],
			'points' => [
				'numeric' => app_lang('invalid_points')
			],
			'deadline' => [
				'valid_date' => app_lang('invalid_deadline')
			],
			'start_date' => [
				'valid_date' => app_lang('invalid_start_date')
			]
		];

		if (!$this->validate($rules, $error)) {
			return $this->failValidationErrors($this->validator->getErrors());
		}

		// Validate assigned user if provided
		if (isset($posted_data['assigned_to'])) {
			$is_user_exists = $this->users_model->get_details(['id' => $posted_data['assigned_to']])->getResult();
			if (empty($is_user_exists)) {
				return $this->failValidationError(app_lang('invalid_assigned_to'));
			}
		}

		// Validate status if provided
		if (isset($posted_data['status_id'])) {
			$is_status_exists = $this->task_status_model->get_details(['id' => $posted_data['status_id']])->getResult();
			if (empty($is_status_exists)) {
				return $this->failValidationError(app_lang('invalid_status_id'));
			}
		}

		// Validate priority if provided
		if (isset($posted_data['priority_id'])) {
			$is_priority_exists = $this->task_priority_model->get_details(['id' => $posted_data['priority_id']])->getResult();
			if (empty($is_priority_exists)) {
				return $this->failValidationError(app_lang('invalid_priority_id'));
			}
		}

		$update_data = [
			'title'        => $posted_data['title'] ?? $is_task_exists['title'],
			'description'  => $posted_data['description'] ?? $is_task_exists['description'],
			'assigned_to'  => $posted_data['assigned_to'] ?? $is_task_exists['assigned_to'],
			'status_id'    => $posted_data['status_id'] ?? $is_task_exists['status_id'],
			'priority_id'  => $posted_data['priority_id'] ?? $is_task_exists['priority_id'],
			'milestone_id' => $posted_data['milestone_id'] ?? $is_task_exists['milestone_id'],
			'deadline'     => $posted_data['deadline'] ?? $is_task_exists['deadline'],
			'start_date'   => $posted_data['start_date'] ?? $is_task_exists['start_date'],
			'points'       => $posted_data['points'] ?? $is_task_exists['points'],
			'labels'       => $posted_data['labels'] ?? $is_task_exists['labels'],
			'collaborators'=> $posted_data['collaborators'] ?? $is_task_exists['collaborators'],
		];

		$data = clean_data($update_data);

		if ($this->tasks_model->ci_save($data, $id)) {
			$response = [
				'status'   => 200,
				'messages' => [
					'success' => app_lang('task_update_success')
				]
			];
			return $this->respondCreated($response);
		}
		
		return $this->fail(app_lang('task_update_fail'), 400);
	}

	/**
	 * @api {delete} api/tasks/:id Delete a Task
	 * @apiVersion 1.0.0
	 * @apiName deleteTask
	 * @apiGroup Tasks
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam {Number} id Task unique ID.
	 *
	 * @apiSuccess {String} status Request status.
	 * @apiSuccess {String} message Task Deleted Successfully.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Task Deleted Successfully."
	 *     }
	 *
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) {
			return $this->fail(app_lang('invalid_task_id'), 404);
		}
		
		if ($this->tasks_model->get_details(['id' => $id])->getResult()) {
			if ($this->tasks_model->delete($id)) {
				$response = [
					'status'   => 200,
					'messages' => [
						'success' => app_lang('task_delete_success')
					]
				];
				return $this->respondDeleted($response);
			}
			
			return $this->fail(app_lang('task_delete_fail'), 400);
		}
		
		return $this->fail(app_lang('task_delete_fail'), 400);
	}
}
