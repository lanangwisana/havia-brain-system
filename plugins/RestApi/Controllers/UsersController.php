<?php

namespace RestApi\Controllers;

class UsersController extends Rest_api_Controller {
	protected $UsersModel = 'RestApi\Models\UsersModel';

	public function __construct() {
		parent::__construct();

		$this->users_model = model('App\Models\Users_model');
		$this->restapi_users_model = model($this->UsersModel);
		$this->clients_model = model('App\Models\Clients_model');
		$this->roles_model = model('App\Models\Roles_model');
	}

	/**
	 * @api {get} /api/users List all Users
	 * @apiVersion 1.0.0
	 * @apiName getUsers
	 * @apiGroup Users
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {String} [user_type] Optional filter by user type (staff, client, lead)
	 * @apiParam (query) {String} [status] Optional filter by status (active, inactive)
	 *
	 * @apiSuccess {Object} Users information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"first_name": "John",
	 * 		"last_name": "Doe",
	 * 		"user_type": "staff",
	 * 		"is_admin": "1",
	 * 		"role_id": "0",
	 * 		"email": "john@example.com",
	 * 		"image": null,
	 * 		"status": "active",
	 * 		"client_id": "0",
	 * 		"is_primary_contact": "0",
	 * 		"job_title": "Admin",
	 * 		"phone": "1234567890",
	 * 		"gender": "male",
	 * 		"created_at": "2021-09-12 10:00:00"
	 * }
	 *
	 */
	public function index() {
		$options = [];
		
		if ($this->request->getGet('user_type')) {
			$options['user_type'] = $this->request->getGet('user_type');
		}
		
		if ($this->request->getGet('status')) {
			$options['status'] = $this->request->getGet('status');
		}

		$list_data = $this->users_model->get_details($options)->getResult();
		
		if (empty($list_data)) {
			return $this->failNotFound(app_lang('no_data_were_found'));
		}

		// Remove password field from response
		foreach ($list_data as $user) {
			unset($user->password);
		}

		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/users/:id Get User by ID
	 * @apiVersion 1.0.0
	 * @apiName showUser
	 * @apiGroup Users
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id User unique ID
	 *
	 * @apiSuccess {Object} User information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"first_name": "John",
	 * 		"last_name": "Doe",
	 * 		"user_type": "staff",
	 * 		"is_admin": "1",
	 * 		"role_id": "0",
	 * 		"email": "john@example.com",
	 * 		"image": null,
	 * 		"status": "active",
	 * 		"client_id": "0",
	 * 		"job_title": "Admin",
	 * 		"phone": "1234567890",
	 * 		"gender": "male",
	 * 		"created_at": "2021-09-12 10:00:00"
	 * }
	 *
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->users_model->get_details(['id' => $id])->getRow();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			// Remove password field from response
			unset($list_data->password);

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('invalid_user_id'));
	}

	/**
	 * @api {get} /api/users/search/:keyword Search Users
	 * @apiVersion 1.0.0
	 * @apiName searchUsers
	 * @apiGroup Users
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {String} keyword Search keyword
	 *
	 * @apiSuccess {Object} Users information
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "first_name": "John",
	 *     "last_name": "Doe",
	 *     "email": "john@example.com",
	 *     "user_type": "staff",
	 *     "status": "active"
	 *   }
	 * ]
	 *
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_users_model->get_search_suggestion($key)->getResult();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			// Remove password field from response
			foreach ($list_data as $user) {
				unset($user->password);
			}

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/users Add New User
	 * @apiVersion 1.0.0
	 * @apiName createUser
	 * @apiGroup Users
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} first_name Mandatory User first name
	 * @apiParam (body) {string} last_name Mandatory User last name
	 * @apiParam (body) {string} email Mandatory User email
	 * @apiParam (body) {string} user_type Mandatory User type (staff, client, lead)
	 * @apiParam (body) {string} [password] Optional User password
	 * @apiParam (body) {string} [phone] Optional User phone
	 * @apiParam (body) {string} [job_title] Optional Job title
	 * @apiParam (body) {string} [gender] Optional Gender (male, female, other)
	 * @apiParam (body) {number} [client_id] Optional Client ID (required for client user_type)
	 * @apiParam (body) {number} [role_id] Optional Role ID (for staff)
	 * @apiParam (body) {string} [status] Optional Status (active, inactive) default: active
	 * @apiParam (body) {string} [address] Optional Address
	 * @apiParam (body) {string} [skype] Optional Skype ID
	 *
	 * @apiParamExample Request-Example:
	 *     {
	 *        "first_name": "John",
	 *        "last_name": "Doe",
	 *        "email": "john.doe@example.com",
	 *        "user_type": "staff",
	 *        "password": "password123",
	 *        "phone": "1234567890",
	 *        "job_title": "Developer",
	 *        "gender": "male",
	 *        "role_id": 1,
	 *        "status": "active"
	 *     }
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message User add successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "User add successful."
	 *     }
	 *
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		
		if (!empty($posted_data)) {
			$rules = [
				'first_name' => 'required',
				'last_name'  => 'required',
				'email'      => 'required|valid_email',
				'user_type'  => 'required|in_list[staff,client,lead]',
				'phone'      => 'numeric|if_exist',
				'gender'     => 'in_list[male,female,other]|if_exist',
				'status'     => 'in_list[active,inactive]|if_exist',
				'client_id'  => 'numeric|if_exist',
				'role_id'    => 'numeric|if_exist'
			];

			$error = [
				'first_name' => [
					'required' => app_lang('first_name_required')
				],
				'last_name' => [
					'required' => app_lang('last_name_required')
				],
				'email' => [
					'required'    => app_lang('email_required'),
					'valid_email' => app_lang('valid_email')
				],
				'user_type' => [
					'required' => app_lang('user_type_required'),
					'in_list'  => app_lang('valid_user_type')
				],
				'phone' => [
					'numeric' => app_lang('valid_phone')
				],
				'gender' => [
					'in_list' => app_lang('valid_gender')
				],
				'status' => [
					'in_list' => app_lang('valid_status')
				],
				'client_id' => [
					'numeric' => app_lang('valid_client_id')
				],
				'role_id' => [
					'numeric' => app_lang('valid_role_id')
				]
			];

			if (!$this->validate($rules, $error)) {
				return $this->failValidationErrors($this->validator->getErrors());
			}

			// Check if email already exists
			$email_exists = $this->users_model->get_details(['email' => $posted_data['email']])->getRow();
			if (!empty($email_exists)) {
				return $this->failValidationError(app_lang('email_already_exists'));
			}

			// Validate client_id if user_type is client or lead
			if (($posted_data['user_type'] == 'client' || $posted_data['user_type'] == 'lead') && isset($posted_data['client_id'])) {
				$is_client_exists = $this->clients_model->get_details(['id' => $posted_data['client_id']])->getResult();
				if (empty($is_client_exists)) {
					return $this->failValidationError(app_lang('invalid_client_id'));
				}
			}

			// Validate role_id if provided
			if (isset($posted_data['role_id']) && $posted_data['role_id'] > 0) {
				$is_role_exists = $this->roles_model->get_details(['id' => $posted_data['role_id']])->getResult();
				if (empty($is_role_exists)) {
					return $this->failValidationError(app_lang('invalid_role_id'));
				}
			}

			$insert_data = [
				'first_name'  => $posted_data['first_name'],
				'last_name'   => $posted_data['last_name'],
				'email'       => $posted_data['email'],
				'user_type'   => $posted_data['user_type'],
				'status'      => $posted_data['status'] ?? 'active',
				'phone'       => $posted_data['phone'] ?? '',
				'job_title'   => $posted_data['job_title'] ?? 'Untitled',
				'gender'      => $posted_data['gender'] ?? null,
				'client_id'   => $posted_data['client_id'] ?? 0,
				'role_id'     => $posted_data['role_id'] ?? 0,
				'address'     => $posted_data['address'] ?? null,
				'skype'       => $posted_data['skype'] ?? null,
				'created_at'  => date('Y-m-d H:i:s'),
			];

			// Hash password if provided
			if (isset($posted_data['password']) && !empty($posted_data['password'])) {
				$insert_data['password'] = password_hash($posted_data['password'], PASSWORD_DEFAULT);
			}

			$data = clean_data($insert_data);

			$save_id = $this->users_model->ci_save($data);
			
			if ($save_id > 0 && !empty($save_id)) {
				$response = [
					'status'   => 200,
					'messages' => [
						'success' => app_lang('user_add_success')
					],
					'id' => $save_id
				];
				return $this->respondCreated($response);
			}
			
			return $this->fail(app_lang('user_add_fail'), 400);
		}
		
		return $this->fail(app_lang('user_add_fail'), 400);
	}

	/**
	 * @api {put} api/users/:id Update a User
	 * @apiVersion 1.0.0
	 * @apiName updateUser
	 * @apiGroup Users
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id User unique ID
	 * @apiParam (body) {string} [first_name] Optional User first name
	 * @apiParam (body) {string} [last_name] Optional User last name
	 * @apiParam (body) {string} [email] Optional User email
	 * @apiParam (body) {string} [phone] Optional User phone
	 * @apiParam (body) {string} [job_title] Optional Job title
	 * @apiParam (body) {string} [gender] Optional Gender
	 * @apiParam (body) {string} [status] Optional Status (active, inactive)
	 * @apiParam (body) {string} [address] Optional Address
	 * @apiParam (body) {string} [skype] Optional Skype ID
	 *
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	    "first_name": "Updated Name",
	 *	    "last_name": "Updated Last",
	 *	    "phone": "9876543210",
	 *	    "job_title": "Senior Developer",
	 *	    "status": "active"
	 *	}
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message User Update Successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "User Update Successful."
	 *     }
	 *
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_user_exists = $this->users_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_user_exists)) {
			return $this->fail(app_lang('invalid_user_id'), 404);
		}

		$rules = [
			'first_name' => 'required|if_exist',
			'last_name'  => 'required|if_exist',
			'email'      => 'required|valid_email|if_exist',
			'phone'      => 'numeric|if_exist',
			'gender'     => 'in_list[male,female,other]|if_exist',
			'status'     => 'in_list[active,inactive]|if_exist',
		];

		$error = [
			'first_name' => [
				'required' => app_lang('first_name_required')
			],
			'last_name' => [
				'required' => app_lang('last_name_required')
			],
			'email' => [
				'required'    => app_lang('email_required'),
				'valid_email' => app_lang('valid_email')
			],
			'phone' => [
				'numeric' => app_lang('valid_phone')
			],
			'gender' => [
				'in_list' => app_lang('valid_gender')
			],
			'status' => [
				'in_list' => app_lang('valid_status')
			]
		];

		if (!$this->validate($rules, $error)) {
			$response = [
				'error' => $this->validator->getErrors(),
			];
			return $this->fail($response);
		}

		// Check if email already exists (for other users)
		if (isset($posted_data['email']) && $posted_data['email'] != $is_user_exists['email']) {
			$email_exists = $this->users_model->get_details(['email' => $posted_data['email']])->getRow();
			if (!empty($email_exists)) {
				return $this->failValidationError(app_lang('email_already_exists'));
			}
		}

		$update_data = [
			'first_name' => $posted_data['first_name'] ?? $is_user_exists['first_name'],
			'last_name'  => $posted_data['last_name'] ?? $is_user_exists['last_name'],
			'email'      => $posted_data['email'] ?? $is_user_exists['email'],
			'phone'      => $posted_data['phone'] ?? $is_user_exists['phone'],
			'job_title'  => $posted_data['job_title'] ?? $is_user_exists['job_title'],
			'gender'     => $posted_data['gender'] ?? $is_user_exists['gender'],
			'status'     => $posted_data['status'] ?? $is_user_exists['status'],
			'address'    => $posted_data['address'] ?? $is_user_exists['address'],
			'skype'      => $posted_data['skype'] ?? $is_user_exists['skype'],
		];

		$data = clean_data($update_data);

		if ($this->users_model->ci_save($data, $id)) {
			$response = [
				'status'   => 200,
				'messages' => [
					'success' => app_lang('user_update_success')
				]
			];
			return $this->respondCreated($response);
		}
		
		return $this->fail(app_lang('user_update_fail'), 400);
	}

	/**
	 * @api {delete} api/users/:id Delete a User
	 * @apiVersion 1.0.0
	 * @apiName deleteUser
	 * @apiGroup Users
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id User unique ID
	 *
	 * @apiSuccess {String} status Request status.
	 * @apiSuccess {String} message User Deleted Successfully.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "User Deleted Successfully."
	 *     }
	 *
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) {
			return $this->fail(app_lang('invalid_user_id'), 404);
		}
		
		if ($this->users_model->get_details(['id' => $id])->getResult()) {
			if ($this->users_model->delete($id)) {
				$response = [
					'status'   => 200,
					'messages' => [
						'success' => app_lang('user_delete_success')
					]
				];
				return $this->respondDeleted($response);
			}
			
			return $this->fail(app_lang('user_delete_fail'), 400);
		}
		
		return $this->fail(app_lang('user_delete_fail'), 400);
	}
}
