<?php
namespace Custom\Widgets\rc_utils;
use RightNow\Connect\v1_3 as RNCPHP;
  
/**
 * 
 */
class UrlMaker extends \RightNow\Libraries\Widget\Base
{
  private $key            = "4RC";
  private $alg            = 'HS256';
  private $lastError      = '';
  private $id             = 0;
  private $global_remaining_time;
  private $global_incident;

    
  /**
   * 
   */
  function __construct($attrs)
  {
    parent::__construct($attrs);

    \RightNow\Libraries\AbuseDetection::check();

    $this->CI->load->helper('utils_helper');
    $this->CI->load->model('custom/rcModel');
    $this->CI->load->library('JWT2');

    $this->setAjaxHandlers(array(
      'getURL_ajax_endpoint' => array(
        'method'      => 'handle_getURL_ajax_endpoint',
        'clickstream' => 'getURL_ajax_endpoint',
      ),
      'default_ajax_endpoint' => array(
        'method'      => 'handle_default_ajax_endpoint',
        'clickstream' => 'custom_action',
      ),
    ));
  }
  
  /**
   * 
   */
  function getData()
  {
    if($this->CI->rcModel->alreadyJWT($this->data['attrs']['incident_id']) != "")
    {
      $this->data['URL_field'] = $this->fullFillData();
    }
    return parent::getData();
  }

  function fullFillData()
  {
    $jwt    = $this->CI->rcModel->alreadyJWT($this->data['attrs']['incident_id']);
    $a_data = $this->CI->rcModel->setAtributes($jwt);
    $this->data['js']['remaining_time'] = gmdate("d-m-Y H:i:s",  $a_data->exp);
    $this->data['js']['status']         = $a_data->incident->StatusWithType->Status->LookupName;    
    $this->data['js']['ref_number']     = $a_data->incident_ref;
    return $jwt;
  }
  /**
   * 
   */
  function makeJWT($month, $day, $hours, $minutes)
  {
    $incident_id            = $this->data['attrs']['incident_id'];
    $global_incident        = $this->CI->rcModel->getIncidentByID($incident_id);
    $keyStoreData           = array("incident"  => $global_incident->ReferenceNumber,"status" => $global_incident->StatusWithType->Status->ID);
    $iat_time               = time();
    $exp_time               = $this->convertTime($month,$day,$hours,$minutes);

    $global_remaining_time  = $iat_time + $exp_time ;
    $token = array(
      'iat'  => $iat_time,
      'exp'  => $iat_time + $exp_time,
      'data' => $keyStoreData
    );

    $jwt        = $this->CI->jwt2->encode($token, "4RC", "HS256");
    $global_url = $jwt;
    $this->CI->rcModel->saveURLwithIncident($jwt,$incident_id);

    $a_return = array(
      "jwt"                   => $jwt,
      "global_remaining_time" => $global_remaining_time,
      "global_incident"       => $global_incident
    );

    return $a_return;
  }
  
  /**
   * 
   */
  function handle_getURL_ajax_endpoint($params)
  {
    header('Content-Type: application/json');

    $domain  = $_SERVER['SERVER_NAME'];
    $fields  = "/app/rc_utils/FileLoad/t/";
    $param   = json_decode($params['data'], true);

    $day     = ($param["day"])      ? $param["day"]     : 0;
    $hours   = ($param["hours"])    ? $param["hours"]   : 0;
    $minutes = ($param["minutes"])  ? $param["minutes"] : 0;

    $a_token                           = $this->makeJWT($month,$day,$hours,$minutes);
    $noCompa                           = "?nocompatibility";
    $URL                               = $domain.$fields.$a_token["jwt"].$noCompa;
    $response                          = new \stdClass();
    $response->success                 = true;
    $response->message                 = new \stdClass();
    $response->message->URL            = $URL;
    $response->message->remaining_time = $a_token["global_remaining_time"];
    $response->message->status         = $a_token["global_incident"]->StatusWithType->Status->LookupName;
    $response->message->ref_number     = $a_token["global_incident"]->ReferenceNumber;
    echo json_encode($response);
  }
  
  function handle_default_ajax_endpoint()
  {

  }
  /**
   * 
   */
  function convertTime($month,$day,$hour,$minute)
  {
    $dias       = 86400;
    $mes        = $dias * 31;
    $segundos   = 1;
    $minutos    = 60;
    $horas      = 3600;
    
    $calculated = ($month * $mes) + ($day * $dias) + ($hour * $horas) + ($minute * $minutos);
    
    return $calculated;
  }
}