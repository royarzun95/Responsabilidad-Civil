<?php
namespace Custom\Widgets\rc_utils;
use RightNow\Connect\v1_3 as RNCPHP;
use stdClass;

class TicketRCGenerator extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        \RightNow\Libraries\AbuseDetection::check();
        $this->CI->load->helper('utils_helper');//helper
        $this->CI->load->library('JWT2');
        $this->CI->load->model('custom/rcModel');
        $this->CI->load->model('custom/ContactGeneral');
        $this->CI->load->model('custom/IncidentGeneral');
        $this->CI->load->model('custom/Files');
        $this->setAjaxHandlers(array(
            'send_incident' => array(
                'method'      => 'handle_send_incident',
                'clickstream' => 'custom_action',
            ),
        ));
    }

    function getData()
    {
        if($this->data['attrs']['incident'])
        {
            $inc                        = $this->data['attrs']['incident'];
            $incident                   = $this->CI->IncidentGeneral->get($inc);
            $this->data["father"]       = $incident->CustomFields->REALE->Request->ReferenceNumber;
            $this->data["plate"]        = $incident->CustomFields->REALE->request_description;
            $this->data["description"]  = $incident->CustomFields->c->vehicle_plate;
        }
        return parent::getData();
    }

    /**
     * Handles the send_incident AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_send_incident($params)
    {
        $param              = json_decode($params['data'], true);
        $father             = $param["father"];
        $plate              = $param["plate"];
        $description        = $param["description"];
        $our_rc_incident    = $this->CI->rcModel->createRCIncident($father,$plate,$description);
        $response           = new \stdClass();
        $response->success  = ($our_rc_incident) ? true:false;
        $response->message  = ($our_rc_incident) ? "exito":"error ->".$this->CI->rcModel->getLastError();
        echo json_encode($response);
    }
}