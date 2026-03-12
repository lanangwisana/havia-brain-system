<?php

namespace RestApi\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class Rest_api_Controller extends ResourceController {
	use ResponseTrait;
	protected $format = 'json';
	protected $api_settings_model;

	/**
	 * Get created_by from request, validated against users. Fallback to 1 if not provided or invalid.
	 * Use for proper attribution when creating records via API.
	 * @return int
	 */
	protected function getCreatedBy(): int {
		$posted = $this->getRequestData();
		$created_by = $posted['created_by'] ?? 1;
		if (!is_numeric($created_by) || (int) $created_by < 1) return 1;
		$users_model = model('App\Models\Users_model');
		$user = $users_model->get_details(['id' => (int) $created_by])->getRow();
		return (!empty($user)) ? (int) $created_by : 1;
	}

	/**
	 * Get request data from JSON body or form-urlencoded body (supports both).
	 * @return array
	 */
	protected function getRequestData(): array {
		$data = [];
		$contentType = $this->request->getHeaderLine('Content-Type');
		if (strpos($contentType, 'application/json') !== false) {
			try {
				$data = $this->request->getJSON(true);
			} catch (\Throwable $e) {
				$data = [];
			}
		}
		if (empty($data) || !is_array($data)) {
			$data = $this->request->getPost();
		}
		return is_array($data) ? $data : [];
	}

	public function __construct() {
		$this->api_settings_model = model('RestApi\Models\Api_settings_model');
		helper('jwt');
		$is_valid_token = validateToken();
		$token          = get_token();
		$check_token    = $this->api_settings_model->check_token($token);
		if ($is_valid_token['status'] == false || $check_token === false) {
			$message = [
				'status'  => false,
				'message' => $is_valid_token['message'] ?? "Token not found"
			];
			$this->response = service('response');
			echo $this->format($message);
			die;
		}
	}
}

/* End of file Rest_api_Controller.php */
/* Location: ./plugins/RestAPI/controllers/Rest_api_Controller.php */
