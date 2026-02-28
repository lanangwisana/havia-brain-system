<?php

namespace RestApi\Controllers;

class TicketCommentsController extends Rest_api_Controller {
	protected $TicketCommentsModel = 'RestApi\Models\TicketCommentsModel';

	public function __construct() {
		parent::__construct();
		$this->ticket_comments_model = model('App\Models\Ticket_comments_model');
		$this->restapi_ticket_comments_model = model($this->TicketCommentsModel);
		$this->tickets_model = model('App\Models\Tickets_model');
	}

	/**
	 * @api {get} /api/ticket-comments List all Ticket Comments
	 * @apiVersion 1.0.0
	 * @apiName getTicketComments
	 * @apiGroup TicketComments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [ticket_id] Optional filter by ticket ID
	 *
	 * @apiSuccess {Object[]} data List of ticket comments
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "ticket_id": "1",
	 *     "description": "Comment text",
	 *     "created_by": "1"
	 *   }
	 * ]
	 */
	public function index() {
		$options = [];
		if ($this->request->getGet('ticket_id')) $options['ticket_id'] = $this->request->getGet('ticket_id');

		$list_data = $this->ticket_comments_model->get_details($options)->getResult();
		if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/ticket-comments/:id Get Ticket Comment by ID
	 * @apiVersion 1.0.0
	 * @apiName showTicketComment
	 * @apiGroup TicketComments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Ticket comment information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "1",
	 *   "ticket_id": "1",
	 *   "description": "Comment text"
	 * }
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->ticket_comments_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('invalid_comment_id'));
	}

	/**
	 * @api {get} /api/ticket-comments/search/:keyword Search Ticket Comments
	 * @apiVersion 1.0.0
	 * @apiName searchTicketComments
	 * @apiGroup TicketComments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object[]} data Search results
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "description": "Comment text"
	 *   }
	 * ]
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_ticket_comments_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) return $this->failNotFound(app_lang('no_data_were_found'));
			return $this->respond($list_data, 200);
		}
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/ticket-comments Add Ticket Comment
	 * @apiVersion 1.0.0
	 * @apiName createTicketComment
	 * @apiGroup TicketComments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {number} ticket_id Mandatory Ticket ID
	 * @apiParam (body) {string} description Mandatory Comment content
	 * @apiParam (body) {number} [created_by] Optional RISE user ID (commenter). Default: 1. Use for proper attribution.
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccess {Number} id Created comment ID
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 Created
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Comment add success"},
	 *       "id": 1
	 *     }
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = ['ticket_id' => 'required|numeric', 'description' => 'required'];
			if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

			$insert_data = [
				'ticket_id' => $posted_data['ticket_id'],
				'description' => $posted_data['description'],
				'created_by' => 1,
				'created_at' => date('Y-m-d H:i:s'),
				'files' => serialize([])
			];

			$data = clean_data($insert_data);
			$save_id = $this->ticket_comments_model->ci_save($data);
			if ($save_id > 0) {
				return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('comment_add_success')], 'id' => $save_id]);
			}
			return $this->fail(app_lang('comment_add_fail'), 400);
		}
		return $this->fail(app_lang('comment_add_fail'), 400);
	}

	/**
	 * @api {put} api/ticket-comments/:id Update Ticket Comment
	 * @apiVersion 1.0.0
	 * @apiName updateTicketComment
	 * @apiGroup TicketComments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Ticket comment unique ID
	 * @apiParam (body) {string} [description] Optional Comment content to update
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Comment update success"}
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_comment_exists = $this->ticket_comments_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_comment_exists)) {
			return $this->failNotFound(app_lang('invalid_comment_id'));
		}

		$update_data = ['description' => $posted_data['description'] ?? $is_comment_exists['description']];
		$data = clean_data($update_data);

		if ($this->ticket_comments_model->ci_save($data, $id)) {
			return $this->respondCreated(['status' => 200, 'messages' => ['success' => app_lang('comment_update_success')]]);
		}
		return $this->fail(app_lang('comment_update_fail'), 400);
	}

	/**
	 * @api {delete} api/ticket-comments/:id Delete Ticket Comment
	 * @apiVersion 1.0.0
	 * @apiName deleteTicketComment
	 * @apiGroup TicketComments
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Number} status HTTP status (200)
	 * @apiSuccess {Object} messages Success message
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Comment delete success"}
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) return $this->failNotFound(app_lang('invalid_comment_id'));
		
		if ($this->ticket_comments_model->get_details(['id' => $id])->getResult()) {
			if ($this->ticket_comments_model->delete($id)) {
				return $this->respondDeleted(['status' => 200, 'messages' => ['success' => app_lang('comment_delete_success')]]);
			}
		}
		return $this->fail(app_lang('comment_delete_fail'), 400);
	}
}
