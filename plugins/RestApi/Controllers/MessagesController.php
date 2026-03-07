<?php

namespace RestApi\Controllers;

class MessagesController extends Rest_api_Controller {
	protected $MessagesModel = 'RestApi\Models\MessagesModel';

	public function __construct() {
		parent::__construct();
		$this->messages_model = model('App\Models\Messages_model');
		$this->restapi_messages_model = model($this->MessagesModel);
		$this->users_model = model('App\Models\Users_model');
	}

	/**
	 * @api {get} /api/messages List all Messages
	 * @apiVersion 1.0.0
	 * @apiName getMessages
	 * @apiGroup Messages
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [from_user_id] Optional filter by sender
	 * @apiParam (query) {Number} [to_user_id] Optional filter by recipient
	 * @apiParam (query) {String} [status] Optional filter by status (read, unread)
	 *
	 * @apiSuccess {Object[]} data List of messages
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "subject": "Hello",
	 *     "message": "Content",
	 *     "from_user_id": "1",
	 *     "to_user_id": "2",
	 *     "status": "unread"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('from_user_id')) $options['from_user_id'] = $this->request->getGet('from_user_id');
		if ($this->request->getGet('to_user_id')) $options['to_user_id'] = $this->request->getGet('to_user_id');
		if ($this->request->getGet('status')) $options['status'] = $this->request->getGet('status');

		$list_data = $this->messages_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/messages/:id Get Message by ID
	 * @apiVersion 1.0.0
	 * @apiName showMessage
	 * @apiGroup Messages
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Message information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "subject": "Hello",
	 *   "message": "Content",
	 *   "from_user_id": "1",
	 *   "to_user_id": "2",
	 *   "status": "unread"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->messages_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_message_id'));
	}

	/**
	 * @api {get} /api/messages/search/:keyword Search Messages
	 * @apiVersion 1.0.0
	 * @apiName searchMessages
	 * @apiGroup Messages
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results (messages)
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "subject": "Hello"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_messages_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/messages Add New Message
	 * @apiVersion 1.0.0
	 * @apiName createMessage
	 * @apiGroup Messages
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} subject Mandatory Message subject
	 * @apiParam (body) {string} message Mandatory Message content
	 * @apiParam (body) {number} from_user_id Mandatory Sender user ID
	 * @apiParam (body) {number} to_user_id Mandatory Recipient user ID
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created message ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Message add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = ['subject' => 'required', 'message' => 'required', 'from_user_id' => 'required|numeric', 'to_user_id' => 'required|numeric'];
			if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

			$insert_data = [
				'subject' => $posted_data['subject'],
				'message' => $posted_data['message'],
				'from_user_id' => $posted_data['from_user_id'],
				'to_user_id' => $posted_data['to_user_id'],
				'status' => 'unread',
				'created_at' => date('Y-m-d H:i:s'),
				'files' => serialize([]),
				'deleted_by_users' => ''
			];

			$data = clean_data($insert_data);
			$save_id = $this->messages_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('message_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('message_add_fail'), 400);
		}
		return $this->fail(app_lang('message_add_fail'), 400);
	}

	/**
	 * @api {put} api/messages/:id Update a Message
	 * @apiVersion 1.0.0
	 * @apiName updateMessage
	 * @apiGroup Messages
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Message unique ID
	 * @apiParam (body) {string} [subject] Optional Subject to update
	 * @apiParam (body) {string} [message] Optional Message content to update
	 * @apiParam (body) {string} [status] Optional Status (read, unread) to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Message update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_message_exists = $this->messages_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_message_exists)) {
			return $this->fail(app_lang('invalid_message_id'), 404);
		}

		$update_data = ['status' => $posted_data['status'] ?? $is_message_exists['status']];

		$data = clean_data($update_data);
		if ($this->messages_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('message_update_success')]]);
		}
		return $this->fail(app_lang('message_update_fail'), 400);
	}

	/**
	 * @api {delete} api/messages/:id Delete a Message
	 * @apiVersion 1.0.0
	 * @apiName deleteMessage
	 * @apiGroup Messages
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Message delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->fail(app_lang('invalid_message_id'), 404);
		
		if ($this->messages_model->get_details(['id' => $id])->getResult()) {
			if ($this->messages_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('message_delete_success')]]);
			}
		}
		return $this->fail(app_lang('message_delete_fail'), 400);
	}
}
