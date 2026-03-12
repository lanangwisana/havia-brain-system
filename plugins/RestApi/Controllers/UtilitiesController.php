<?php
namespace RestApi\Controllers;

class UtilitiesController extends Rest_api_Controller {
    public function __construct() {
        parent::__construct();
    }

    public function getClientGroups() {
        $this->Client_groups_model = model("App\Models\Client_groups_model");
        $list_data = $this->Client_groups_model->get_details()->getResult();
        return $this->respond($list_data, 200);
    }

    public function getProejctLabels() {
        $this->labels_model = model("App\Models\Labels_model");
        $list_data = $this->labels_model->get_details(['context' => 'project'])->getResult();
        return $this->respond($list_data, 200);
    }

    public function getInvoiceLabels() {
        $this->labels_model = model("App\Models\Labels_model");
        $list_data = $this->labels_model->get_details(['context' => 'invoice'])->getResult();
        return $this->respond($list_data, 200);
    }

    public function getTicketLabels() {
        $this->labels_model = model("App\Models\Labels_model");
        $list_data = $this->labels_model->get_details(['context' => 'ticket'])->getResult();
        return $this->respond($list_data, 200);
    }
}