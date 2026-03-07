<?php

namespace RestApi\Controllers;

class ClientsController extends Rest_api_Controller {
	protected $ClientsModel = 'RestApi\Models\ClientsModel';

	public function __construct() {
		parent::__construct();

		$this->clients_model         = model('App\Models\Clients_model');
		$this->restapi_clients_model = model($this->ClientsModel);
		$this->users_model           = model('App\Models\Users_model');
		$this->clients_group_model   = model('App\Model\Client_groups_model');
	}


	/**
	 * @api {get} /api/clients/:clientid List all Clients information
	 * @apiVersion 1.0.0
	 * @apiName getClients
	 * @apiGroup Clients
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam {Number} clientid Mandatory Clientid unique ID
	 *
	 * @apiSuccess {Object} Clients information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "2",
	 * 		"company_name": "jdoe",
	 * 		"address": "Rajkot",
	 * 		"city": "",
	 * 		"state": "",
	 * 		"zip": "",
	 * 		"country": "",
	 * 		"created_date": "2021-09-12",
	 * 		"website": "",
	 * 		"phone": "",
	 * 		"currency_symbol": "INR",
	 * 		"starred_by": "",
	 * 		"group_ids": "1",
	 * 		"deleted": "0",
	 * 		"is_lead": "0",
	 * 		"lead_status_id": "1",
	 * 		"owner_id": "1",
	 * 		"created_by": "1",
	 * 		"sort": "0",
	 * 		"lead_source_id": "1",
	 * 		"last_lead_status": "New",
	 * 		"client_migration_date": "2021-09-12",
	 * 		"vat_number": "",
	 * 		"currency": "USD",
	 * 		"disable_online_payment": "1",
	 * 		"primary_contact": "john doe",
	 * 		"primary_contact_id": "4",
	 * 		"contact_avatar": null,
	 * 		"total_projects": "7",
	 * 		"payment_received": "0",
	 * 		"invoice_value": "1188",
	 * 		"client_groups": "Test c group ",
	 * 		"lead_status_title": "New",
	 * 		"lead_status_color": "#f1c40f",
	 * 		"owner_name": "john doe",
	 * 		"owner_avatar": null
	 * }
	 *
	 */
	public function index($clientid = '') {
		$list_data = $this->clients_model->get_details()->getResult();
		if (empty($list_data)) {
			return $this->failNotFound(app_lang('no_data_were_found'));
		}
		return $this->respond($list_data);
	}

	/**
	 * @api {get} /api/clients/:id Get Client by ID
	 * @apiVersion 1.0.0
	 * @apiName showClient
	 * @apiGroup Clients
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiSuccess {Object} Client information
	 * @apiSuccessExample Success-Response:
	 * {
	 *   "id": "2",
	 *   "company_name": "jdoe",
	 *   "address": "Rajkot",
	 *   "city": "",
	 *   "state": "",
	 *   "zip": "",
	 *   "country": "",
	 *   "created_date": "2021-09-12",
	 *   "website": "",
	 *   "phone": "",
	 *   "currency_symbol": "INR",
	 *   "deleted": "0",
	 *   "owner_id": "1"
	 * }
	 *
	 * @return mixed
	 */
	public function show($id = null, $searchTerm = "") {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->clients_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}
			return $this->respond($list_data, 200);
		}
	}

	/**
	 * @api {get} /api/getClientsSearch/search/:keysearch Search Client Information
	 * @apiVersion 1.0.0
	 * @apiName getClientsSearch
	 * @apiGroup Clients
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {String} keysearch Search Keywords
	 *
	 * @apiSuccess {Object} Clients information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "2",
	 * 		"company_name": "jdoe",
	 * 		"address": "Rajkot",
	 * 		"city": "",
	 * 		"state": "",
	 * 		"zip": "",
	 * 		"country": "",
	 * 		"created_date": "2021-09-12",
	 * 		"website": "",
	 * 		"phone": "",
	 * 		"currency_symbol": "INR",
	 * 		"starred_by": "",
	 * 		"group_ids": "1",
	 * 		"deleted": "0",
	 * 		"is_lead": "0",
	 * 		"lead_status_id": "1",
	 * 		"owner_id": "1",
	 * 		"created_by": "1",
	 * 		"sort": "0",
	 * 		"lead_source_id": "1",
	 * 		"last_lead_status": "New",
	 * 		"client_migration_date": "2021-09-12",
	 * 		"vat_number": "",
	 * 		"currency": "USD",
	 * 		"disable_online_payment": "1",
	 * 		"primary_contact": "john doe",
	 * 		"primary_contact_id": "4",
	 * 		"contact_avatar": null,
	 * 		"total_projects": "7",
	 * 		"payment_received": "0",
	 * 		"invoice_value": "1188",
	 * 		"client_groups": "Test c group ",
	 * 		"lead_status_title": "New",
	 * 		"lead_status_color": "#f1c40f",
	 * 		"owner_name": "john doe",
	 * 		"owner_avatar": null
	 * }
	 *
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_clients_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}
			return $this->respond($list_data, 200);
		}
	}

	/**
	 * @api {post} api/clients Add New Client
	 * @apiVersion 1.0.0
	 * @apiName create
	 * @apiGroup Clients
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (body) {string} company_name Mandatory Company Name
	 * @apiParam (body) {string} owner_id Mandatory Company owner id
	 * @apiParam (body) {string} group_ids Optional Company group ids
	 * @apiParam (body) {string} address Optional Company address
	 * @apiParam (body) {string} city Optional Company city
	 * @apiParam (body) {string} state Optional Company state
	 * @apiParam (body) {string} zip Optional Company zip
	 * @apiParam (body) {string} country Optional Company country
	 * @apiParam (body) {string} phone Optional Company phone
	 * @apiParam (body) {string} website Optional Company website
	 * @apiParam (body) {string} vat_number Optional Company vat number
	 * @apiParam (body) {string} disable_online_payment Optional Company disable online payment
	 *
	 * @apiParamExample Request-Example:
	 *     array (size=12)
	 *        'company_name' => string 'Company Name' (length=12)
	 *        'owner_id' => string '1' (length=1)
	 *        'group_ids' => string '1,2' (length=3)
	 *        'address' => string 'test address' (length=12)
	 *        'city' => string 'test city' (length=9)
	 *        'state' => string 'test state' (length=10)
	 *        'zip' => string '123456' (length=6)
	 *        'country' => string 'test country' (length=12)
	 *        'phone' => string '9856231470' (length=10)
	 *        'website' => string 'www.test.com' (length=12)
	 *        'vat_number' => string '123465789' (length=9)
	 *        'start_date' => string '25/07/2019' (length=10)
	 *        'disable_online_payment' => string '0' (length=1)     *
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Client add successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Client add successful."
	 *     }
	 *
	 *
	 *     HTTP/1.1 404 Not Found
	 *     {
	 *       "status": false,
	 *       "message": "Client add fail."
	 *     }
	 *
	 */
	public function create() {
		$posted_data = $this->getRequestData();
		if (!empty($posted_data)) {
			$rules = [
				'company_name'           => 'required|alpha_space',
				'phone'                  => 'numeric|if_exist',
				'website'                => 'valid_url|if_exist',
				'disable_online_payment' => 'greater_than_equal_to[0]|less_than_equal_to[1]|if_exist',
				'owner_id'               => 'required|numeric'
			];
			$error = [
				'company_name' => [
						'required'    => app_lang('company_name_is_required'),
						'alpha_space' => app_lang('valid_company_name')
				],
				'phone' => [
					'numeric' => app_lang('valid_phone')
				],
				'website' => [
					'valid_url' => app_lang('valid_website')
				],
				'disable_online_payment' => [
					'greater_than_equal_to' => app_lang('valid_disable_online_payments'),
					'less_than_equal_to'    => app_lang('valid_disable_online_payments'),
				],
				'owner_id' => [
					'required' => app_lang('owner_is_required'),
					'numeric'  => app_lang('valid_owner_id')
				]
			];
			if (!$this->validate($rules, $error)) {
				return $this->failValidationErrors($this->validator->getErrors());
			}
			$is_owner_exists = $this->users_model->get_details(['id' => $posted_data['owner_id'],['status' => "active"]])->getResult();
			if (empty($is_owner_exists)) {
				$message = app_lang('owner_id_invalid');
				return $this->failValidationError($message);
			}


			if (isset($posted_data['group_ids'])) {
				$group_ids = explode(',', $posted_data['group_ids']);
				foreach ($group_ids as $value) {
					$is_group_id_exits = $this->clients_group_model->get_details(['id' => $value])->getResult();
					if (empty($is_group_id_exits)) {
						$message = app_lang('invalid_group_id')." : ".$value;
						return $this->failValidationError($message);
					}
				}
			}
			$insert_data = [
					'company_name'           => $posted_data['company_name'],
					'address'                => $posted_data['address'] ?? "",
					'city'                   => $posted_data['city'] ?? "",
					'created_date'           => date('Y-m-d'),
					'state'                  => $posted_data['state'] ?? "",
					'zip'                    => $posted_data['zip'] ?? "",
					'country'                => $posted_data['country'] ?? "",
					'phone'                  => $posted_data['phone'] ?? "",
					'website'                => $posted_data['website'] ?? "",
					'vat_number'             => $posted_data['vat_number'] ?? "",
					'disable_online_payment' => $posted_data['disable_online_payment'] ?? 0,
					'is_lead'                => 0,
					'owner_id'               => $posted_data['owner_id'],
					'created_by'             => $posted_data['owner_id']
				];

			$data = clean_data($insert_data);

			$save_id = $this->clients_model->ci_save($data);
			if ($save_id > 0 && !empty($save_id)) {
				$response = [
					  'status'   => 200,
					  'messages' => [
						  'success' => app_lang('client_added_success')
					  ]
					  ];
				return $this->respondCreated($response);
			}
			return $this->fail(app_lang('client_added_fail'), 400);
		}
		return $this->fail(app_lang('client_added_fail'), 400);
	}
	/**
	 * @api {put} api/clients/:id Update a Client
	 * @apiVersion 1.0.0
	 * @apiName update
	 * @apiGroup Clients
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam (path) {Number} id Client unique ID
	 *
	 * @apiParam (body) {string} company_name Optional Company Name
	 * @apiParam (body) {string} owner_id Optional Company owner id
	 * @apiParam (body) {string} group_ids Optional Company group ids
	 * @apiParam (body) {string} address Optional Company address
	 * @apiParam (body) {string} city Optional Company city
	 * @apiParam (body) {string} state Optional Company state
	 * @apiParam (body) {string} zip Optional Company zip
	 * @apiParam (body) {string} country Optional Company country
	 * @apiParam (body) {string} phone Optional Company phone
	 * @apiParam (body) {string} website Optional Company website
	 * @apiParam (body) {string} vat_number Optional Company vat number
	 * @apiParam (body) {string} disable_online_payment Optional Company disable online payment
	 *
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	    "company_name":"Updated Company Name",
	 *	    "owner_id":2,
	 *	    "group_ids":"1,2",
	 *	    "address":"Updated address",
	 * 	    "city":"New City",
	 * 	    "state":"New State",
	 *	    "zip":123456,
	 *	    "country":"New Country",
	 *	    "phone":9876543210,
	 *	    "website":"www.updated-website.com",
	 *	    "vat_number":"VAT123456",
	 *	    "disable_online_payment":0
	 *	}
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Client Update Successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Client Update Successful."
	 *     }
	 *
	 *
	 *     HTTP/1.1 404 Not Found
	 *     {
	 *       "status": false,
	 *       "message": "Client Update Fail."
	 *     }
	 */
	public function update($id = null) {
		$posted_data = $this->getRequestData();
		$is_client_exists = $this->clients_model->get_details(['leads_only' => 0, 'id' => $id])->getRowArray();
		
		if (!is_numeric($id) || empty($is_client_exists)) {
			return $this->fail(app_lang('client_id_invalid'), 404);
		}

		$rules = [
			'company_name'           => 'required|alpha_space|if_exist',
			'phone'                  => 'numeric|if_exist',
			'website'                => 'valid_url|if_exist',
			'disable_online_payment' => 'greater_than_equal_to[0]|less_than_equal_to[1]|if_exist',
			'owner_id'               => 'required|numeric|if_exist'
		];
		$error = [
			'company_name' => [
				'required'    => app_lang('company_name_is_required'),
				'alpha_space' => app_lang('valid_company_name')
			],
			'phone' => [
				'numeric' => app_lang('valid_phone')
			],
			'website' => [
				'valid_url' => app_lang('valid_website')
			],
			'disable_online_payment' => [
				'greater_than_equal_to' => app_lang('valid_disable_online_payments'),
				'less_than_equal_to'    => app_lang('valid_disable_online_payments'),
			],
			'owner_id' => [
				'required' => app_lang('owner_is_required'),
				'numeric'  => app_lang('valid_owner_id')
			]
		];
		
		if (!$this->validate($rules, $error)) {
			return $this->failValidationErrors($this->validator->getErrors());
		}

		if (isset($posted_data['owner_id'])) {
			$is_owner_exists = $this->users_model->get_details(['id' => $posted_data['owner_id'], ['status' => "active"]])->getResult();
			if (empty($is_owner_exists)) {
				$message = app_lang('owner_id_invalid');
				return $this->failValidationError($message);
			}
		}

		if (isset($posted_data['group_ids'])) {
			$group_ids = explode(',', $posted_data['group_ids']);
			foreach ($group_ids as $value) {
				$is_group_id_exits = $this->clients_group_model->get_details(['id' => $value])->getResult();
				if (empty($is_group_id_exits)) {
					$message = app_lang('invalid_group_id')." : ".$value;
					return $this->failValidationError($message);
				}
			}
		}

		$insert_data = [
			'company_name'           => $posted_data['company_name'] ?? $is_client_exists['company_name'],
			'address'                => $posted_data['address'] ?? $is_client_exists['address'],
			'city'                   => $posted_data['city'] ?? $is_client_exists['city'],
			'state'                  => $posted_data['state'] ?? $is_client_exists['state'],
			'zip'                    => $posted_data['zip'] ?? $is_client_exists['zip'],
			'country'                => $posted_data['country'] ?? $is_client_exists['country'],
			'phone'                  => $posted_data['phone'] ?? $is_client_exists['phone'],
			'website'                => $posted_data['website'] ?? $is_client_exists['website'],
			'vat_number'             => $posted_data['vat_number'] ?? $is_client_exists['vat_number'],
			'disable_online_payment' => $posted_data['disable_online_payment'] ?? $is_client_exists['disable_online_payment'],
			'owner_id'               => $posted_data['owner_id'] ?? $is_client_exists['owner_id'],
			'group_ids'              => $posted_data['group_ids'] ?? $is_client_exists['group_ids']
		];

		$data = clean_data($insert_data);

		if ($this->clients_model->ci_save($data, $id)) {
			$response = [
				'status'   => 200,
				'messages' => [
					'success' => app_lang('client_update_success')
				]
			];
			return $this->respondCreated($response);
		}
		
		return $this->fail(app_lang('client_update_fail'), 400);
	}

	/**
	 * @api {delete} api/clients/:id Delete a Client
	 * @apiVersion 1.0.0
	 * @apiName Delete
	 * @apiGroup Clients
	 *
	 * @apiHeader {String} authtoken Authentication token, generated from admin area
	 *
	 * @apiParam {Number} id clients unique ID.
	 *
	 * @apiSuccess {String} status Request status.
	 * @apiSuccess {String} message Client Deleted Successfully.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Client Deleted Successfully."
	 *     }
	 *
	 *
	 *     HTTP/1.1 404 Not Found
	 *     {
	 *       "status": false,
	 *       "message": "Client Delete Fail."
	 *     }
	 **/
	 
	 
	public function delete($id = null) {
		if (!is_numeric($id)) {
			return $this->fail(app_lang('client_id_invalid'), 404);
		}
		if ($this->clients_model->get_details(['leads_only' => 0,'id' => $id])->getResult()) {
			if ($this->clients_model->delete_client_and_sub_items($id)) {
				$response = [
					'status'   => 200,
					'messages' => [
						'success' => app_lang('client_delete_success')
					]
				];
				return $this->respondDeleted($response);
			}
			return $this->fail(app_lang('client_delete_fail'), 400);
		}
		return $this->fail(app_lang('client_delete_fail'), 400);
	}
}
