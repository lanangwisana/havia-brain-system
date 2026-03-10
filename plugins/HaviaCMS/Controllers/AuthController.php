<?php

namespace HaviaCMS\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController {
	use ResponseTrait;
	protected $format = 'json';

	/**
	 * @var \CodeIgniter\HTTP\IncomingRequest
	 */
	protected $request;

	public function __construct() {
		helper('jwt');
	}

	/**
	 * @api {post} /api/login User Login
	 * @apiVersion 1.0.0
	 * @apiName login
	 * @apiGroup Auth
	 *
	 * @apiParam (body) {String} email User email
	 * @apiParam (body) {String} password User password
	 *
	 * @apiSuccess {Boolean} status Request status
	 * @apiSuccess {String} token Authentication token
	 * @apiSuccess {Object} user User information
	 */
	public function login() {
		$users_model = model('App\Models\Users_model');
		$api_settings_model = model('RestApi\Models\Api_settings_model');

		$data = $this->request->getJSON(true);
		if (empty($data)) {
			$data = $this->request->getPost();
		}

		$email = $data['email'] ?? '';
		$password = $data['password'] ?? '';

		if (empty($email) || empty($password)) {
			return $this->fail('Email and password are required', 400);
		}

		// Authenticate using main system model
		if ($users_model->authenticate($email, $password)) {
			$user_info = $users_model->get_details(['email' => $email])->getRow();
			
			if (!$user_info) {
				return $this->fail('User not found', 404);
			}

			// Prepare payload for JWT
			$payload = [
				'id' => $user_info->id,
				'email' => $user_info->email,
				'user_type' => $user_info->user_type,
				'is_admin' => $user_info->is_admin
			];

			// Generate Token
			$token = EncodeJWTtoken($payload);

			// Sync with rise_api_users table to make it valid for other endpoints
			$api_user = $api_settings_model->get_one_where(['user' => $email]);
			
			$api_data = [
				'user' => $email,
				'name' => $user_info->first_name . ' ' . $user_info->last_name,
				'token' => $token,
				'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 year'))
			];

			if ($api_user && !empty($api_user->id)) {
				$api_settings_model->update_data($api_data, ['id' => $api_user->id]);
			} else {
				$api_settings_model->ci_save($api_data);
			}

			// Remove sensitive info
			unset($user_info->password);

			return $this->respond([
				'status' => true,
				'token' => $token,
				'user' => $user_info
			], 200);
		} else {
			return $this->failUnauthorized('Invalid email or password');
		}
	}

	/**
	 * @api {post} /api/register User Registration
	 * @apiVersion 1.0.0
	 * @apiName register
	 * @apiGroup Auth
	 */
	public function register() {
		// For mobile registration, we can use a similar logic to Signup controller but as an API
		// However, RISE CRM usually has specific flows for registration (email verification etc)
		// For now, let's implement a simple version.
		
		$posted_data = $this->request->getJSON(true) ?? $this->request->getPost();
		
		if (empty($posted_data)) {
			return $this->fail('No data provided', 400);
		}

		// Basic validation (can be expanded based on UsersController::create)
		if (empty($posted_data['email']) || empty($posted_data['password']) || empty($posted_data['first_name']) || empty($posted_data['last_name'])) {
			return $this->fail('Required fields missing', 400);
		}

		$users_model = model('App\Models\Users_model');
		if ($users_model->is_email_exists($posted_data['email'])) {
			return $this->fail('Email already exists', 400);
		}

		$insert_data = [
			'first_name' => $posted_data['first_name'],
			'last_name' => $posted_data['last_name'],
			'email' => $posted_data['email'],
			'password' => password_hash($posted_data['password'], PASSWORD_DEFAULT),
			'user_type' => $posted_data['user_type'] ?? 'client', // Default to client for mobile reg
			'created_at' => date('Y-m-d H:i:s'),
			'status' => 'active'
		];

		$save_id = $users_model->ci_save($insert_data);
		
		if ($save_id) {
			return $this->respond([
				'status' => true,
				'message' => 'Registration successful',
				'id' => $save_id
			], 201);
		}

		return $this->fail('Registration failed', 400);
	}
}
