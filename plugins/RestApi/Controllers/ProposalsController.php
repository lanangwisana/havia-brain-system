<?php

namespace RestApi\Controllers;

class ProposalsController extends Rest_api_Controller {
	protected $ProposalsModel = 'RestApi\Models\ProposalsModel';

	public function __construct() {
		parent::__construct();

		$this->proposals_model = model('App\Models\Proposals_model');
		$this->restapi_proposals_model = model($this->ProposalsModel);
		$this->clients_model = model('App\Models\Clients_model');
		$this->projects_model = model('App\Models\Projects_model');
		$this->taxes_model = model('App\Models\Taxes_model');
	}

	/**
	 * @api {get} /api/proposals List all Proposals
	 * @apiVersion 1.0.0
	 * @apiName getProposals
	 * @apiGroup Proposals
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (query) {Number} [client_id] Optional filter by client ID
	 * @apiParam (query) {String} [status] Optional filter by status (draft, sent, accepted, declined)
	 *
	 * @apiSuccess {Object} Proposals information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"client_id": "2",
	 * 		"proposal_date": "2021-09-15",
	 * 		"valid_until": "2021-10-15",
	 * 		"status": "sent",
	 * 		"note": "Proposal for new project",
	 * 		"tax_id": "1",
	 * 		"project_id": "1",
	 * 		"discount_amount": "5",
	 * 		"discount_amount_type": "percentage"
	 * }
	 *
	 */
	public function index() {
		$options = [];
		
		if ($this->request->getGet('client_id')) {
			$options['client_id'] = $this->request->getGet('client_id');
		}
		
		if ($this->request->getGet('status')) {
			$options['status'] = $this->request->getGet('status');
		}

		$list_data = $this->proposals_model->get_details($options)->getResult();
		
		if (empty($list_data)) {
			return $this->failNotFound(app_lang('no_data_were_found'));
		}

		return $this->respond($list_data, 200);
	}

	/**
	 * @api {get} /api/proposals/:id Get Proposal by ID
	 * @apiVersion 1.0.0
	 * @apiName showProposal
	 * @apiGroup Proposals
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Proposal unique ID
	 *
	 * @apiSuccess {Object} Proposal information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "1",
	 * 		"client_id": "2",
	 * 		"proposal_date": "2021-09-15",
	 * 		"valid_until": "2021-10-15",
	 * 		"status": "sent",
	 * 		"note": "Proposal for new project",
	 * 		"tax_id": "1",
	 * 		"project_id": "1",
	 * 		"discount_amount": "5",
	 * 		"discount_amount_type": "percentage"
	 * }
	 *
	 */
	public function show($id = null) {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->proposals_model->get_details(['id' => $id])->getRow();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('invalid_proposal_id'));
	}

	/**
	 * @api {get} /api/proposals/search/:keyword Search Proposals
	 * @apiVersion 1.0.0
	 * @apiName searchProposals
	 * @apiGroup Proposals
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {String} keyword Search keyword
	 *
	 * @apiSuccess {Object} Proposals information
	 * @apiSuccessExample Success-Response:
	 * [
	 *   {
	 *     "id": "1",
	 *     "client_id": "2",
	 *     "proposal_date": "2021-09-15",
	 *     "status": "sent",
	 *     "note": "Proposal for new project"
	 *   }
	 * ]
	 *
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_proposals_model->get_search_suggestion($key)->getResult();
			
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}

			return $this->respond($list_data, 200);
		}
		
		return $this->failNotFound(app_lang('no_data_were_found'));
	}

	/**
	 * @api {post} api/proposals Add New Proposal
	 * @apiVersion 1.0.0
	 * @apiName createProposal
	 * @apiGroup Proposals
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {number} client_id Mandatory Client ID
	 * @apiParam (body) {string} proposal_date Mandatory Proposal date (Y-m-d)
	 * @apiParam (body) {string} valid_until Mandatory Valid until date (Y-m-d)
	 * @apiParam (body) {string} [status] Optional Status (draft, sent, accepted, declined) default: draft
	 * @apiParam (body) {string} [note] Optional Proposal note
	 * @apiParam (body) {string} [content] Optional Proposal content
	 * @apiParam (body) {number} [project_id] Optional Project ID
	 * @apiParam (body) {number} [tax_id] Optional Tax ID
	 * @apiParam (body) {number} [tax_id2] Optional Second tax ID
	 * @apiParam (body) {number} [discount_amount] Optional Discount amount
	 * @apiParam (body) {string} [discount_amount_type] Optional Discount type (percentage, fixed_amount)
	 * @apiParam (body) {number} [created_by] Optional RISE user ID. Default: 1. Use for proper attribution.
	 *
	 * @apiParamExample Request-Example:
	 *     {
	 *        "client_id": 2,
	 *        "proposal_date": "2021-09-15",
	 *        "valid_until": "2021-10-15",
	 *        "status": "draft",
	 *        "content": "Proposal details here",
	 *        "project_id": 1,
	 *        "discount_amount": 5,
	 *        "discount_amount_type": "percentage"
	 *     }
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Proposal add successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Proposal add successful."
	 *     }
	 *
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		
		if (!empty($posted_data)) {
			$rules = [
				'client_id'      => 'required|numeric',
				'proposal_date'  => 'required|valid_date[Y-m-d]',
				'valid_until'    => 'required|valid_date[Y-m-d]',
				'status'         => 'in_list[draft,sent,accepted,declined]|if_exist',
				'project_id'     => 'numeric|if_exist',
				'tax_id'         => 'numeric|if_exist',
				'tax_id2'        => 'numeric|if_exist',
				'discount_amount'=> 'numeric|if_exist'
			];

			$error = [
				'client_id' => [
					'required' => app_lang('client_id_required'),
					'numeric'  => app_lang('invalid_client_id')
				],
				'proposal_date' => [
					'required'   => app_lang('proposal_date_required'),
					'valid_date' => app_lang('invalid_proposal_date')
				],
				'valid_until' => [
					'required'   => app_lang('valid_until_required'),
					'valid_date' => app_lang('invalid_valid_until')
				]
			];

			if (!$this->validate($rules, $error)) {
				return $this->failValidationErrors($this->validator->getErrors());
			}

			$is_client_exists = $this->clients_model->get_details(['id' => $posted_data['client_id']])->getResult();
			if (empty($is_client_exists)) {
				return $this->failValidationError(app_lang('invalid_client_id'));
			}

			$insert_data = [
				'client_id'      => $posted_data['client_id'],
				'proposal_date'  => $posted_data['proposal_date'],
				'valid_until'    => $posted_data['valid_until'],
				'status'         => $posted_data['status'] ?? 'draft',
				'note'           => $posted_data['note'] ?? '',
				'content'        => $posted_data['content'] ?? '',
				'project_id'     => $posted_data['project_id'] ?? 0,
				'tax_id'         => $posted_data['tax_id'] ?? 0,
				'tax_id2'        => $posted_data['tax_id2'] ?? 0,
				'discount_amount'=> $posted_data['discount_amount'] ?? 0,
				'discount_amount_type' => $posted_data['discount_amount_type'] ?? 'percentage',
				'discount_type'  => 'after_tax',
				'created_by'     => $this->getCreatedBy()
			];

			$data = clean_data($insert_data);
			$save_id = $this->proposals_model->ci_save($data);
			
			if ($save_id > 0) {
				return $this->respondCreated([
					'status' => 200,
					'messages' => ['success' => app_lang('proposal_add_success')],
					'id' => $save_id
				]);
			}
			
			return $this->fail(app_lang('proposal_add_fail'), 400);
		}
		
		return $this->fail(app_lang('proposal_add_fail'), 400);
	}

	/**
	 * @api {put} api/proposals/:id Update a Proposal
	 * @apiVersion 1.0.0
	 * @apiName updateProposal
	 * @apiGroup Proposals
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Proposal unique ID
	 * @apiParam (body) {string} [proposal_date] Optional Proposal date to update
	 * @apiParam (body) {string} [valid_until] Optional Valid until date to update
	 * @apiParam (body) {string} [status] Optional Status to update
	 * @apiParam (body) {string} [note] Optional Note to update
	 * @apiParam (body) {string} [content] Optional Content to update
	 * @apiParam (body) {number} [discount_amount] Optional Discount amount to update
	 *
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	    "status": "sent",
	 *	    "note": "Updated proposal note",
	 *	    "discount_amount": 10
	 *	}
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Proposal Update Successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Proposal Update Successful."}
	 *     }
	 *
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_proposal_exists = $this->proposals_model->get_details(['id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_proposal_exists)) {
			return $this->fail(app_lang('invalid_proposal_id'), 404);
		}

		$update_data = [
			'proposal_date'  => $posted_data['proposal_date'] ?? $is_proposal_exists['proposal_date'],
			'valid_until'    => $posted_data['valid_until'] ?? $is_proposal_exists['valid_until'],
			'status'         => $posted_data['status'] ?? $is_proposal_exists['status'],
			'note'           => $posted_data['note'] ?? $is_proposal_exists['note'],
			'content'        => $posted_data['content'] ?? $is_proposal_exists['content'],
			'discount_amount'=> $posted_data['discount_amount'] ?? $is_proposal_exists['discount_amount'],
		];

		$data = clean_data($update_data);
		if ($this->proposals_model->ci_save($data, $id)) {
			return $this->respondCreated([
				'status' => 200,
				'messages' => ['success' => app_lang('proposal_update_success')]
			]);
		}
		
		return $this->fail(app_lang('proposal_update_fail'), 400);
	}

	/**
	 * @api {delete} api/proposals/:id Delete a Proposal
	 * @apiVersion 1.0.0
	 * @apiName deleteProposal
	 * @apiGroup Proposals
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Proposal unique ID.
	 *
	 * @apiSuccess {String} status Request status.
	 * @apiSuccess {String} message Proposal Deleted Successfully.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": {"success": "Proposal Deleted Successfully."}
	 *     }
	 *
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) {
			return $this->fail(app_lang('invalid_proposal_id'), 404);
		}
		
		if ($this->proposals_model->get_details(['id' => $id])->getResult()) {
			if ($this->proposals_model->delete($id)) {
				return $this->respondDeleted([
					'status' => 200,
					'messages' => ['success' => app_lang('proposal_delete_success')]
				]);
			}
		}
		
		return $this->fail(app_lang('proposal_delete_fail'), 400);
	}
}
