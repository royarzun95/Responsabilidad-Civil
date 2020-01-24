<?php
namespace Custom\Widgets\rc_utils;
use RightNow\Connect\v1_3 as RNCPHP;
use stdClass;

class FileLoad extends \RightNow\Libraries\Widget\Base
{
  public $key               = "4RC";
  public $alg               = array('HS256');
  public $incident_ref_numb = "";
  public $error_page        = "https://reale--tst2.custhelp.com/app/error/error_id/4";    
  
  function __construct($attrs)
  {
    parent::__construct($attrs);
    
    \RightNow\Libraries\AbuseDetection::check();

    $this->CI->load->helper('utils_helper');//helper
    $this->CI->load->library('JWT2');
    $this->CI->load->model('custom/rcModel');
    $this->CI->load->model('custom/ContactGeneral');
    $this->CI->load->model('custom/IncidentGeneral');
    $this->CI->load->model('custom/Files');
    $this->setAjaxHandlers(array(
      'default_ajax_endpoint' => array(
        'method'    => 'handle_default_ajax_endpoint',
        'clickstream' => 'updateContact',
      ),
      'update_status_ajax' => array(
        'method'    => 'handle_update_status_ajax_endpoint',
        'clickstream' => 'update_status',
      ),
      'update_normalized_docs' => array(
        'method'    => 'handle_update_normalized_docs_ajax_endpoint',
        'clickstream' => 'update_status',
      )

    ));
  }

  function getData()
  {
    $t     = $this->data['attrs']['new_token'];
    $datax = $this->CI->rcModel->setAtributes($t);
    if($datax)
    {
      if($t == $datax->incident->CustomFields->c->text_generic_email)
      {
        $this->data['js']['allowed_status']        = array(161,185,2,178);
        $this->data['js']['ticket_status']         = $datax->incident->StatusWithType->Status->ID;
        $this->data['js']['incident']              = $datax->incident->ID;
        $this->data['js']['tokenized']             = $t;
        $this->data['js']['counter']               = 0;
        $this->data['js']["net_amount"]            = $datax->incident->CustomFields->c->net_amount;
        $this->data['js']["incident"]              = $datax->incident->ID;
        $this->data["incident"]                    = $datax->incident->ID;
        $this->data["final_amount"]                = $datax->incident->CustomFields->c->net_amount;
        $this->data["person_rut"]                  = $datax->incident->PrimaryContact->CustomFields->c->rut."-".$datax->incident->PrimaryContact->CustomFields->c->dv;
        $this->data["person_name"]                 = $datax->incident->PrimaryContact->Name->First;
        $this->data["person_mobile"]               = $datax->incident->PrimaryContact->Phones[0]->Number;
        $this->data["person_email"]                = $datax->incident->PrimaryContact->Emails[0]->Address;
        $this->data["person_lastname"]             = $datax->incident->PrimaryContact->Name->Last;
        $this->data["person_identificationType"]   = $datax->incident->PrimaryContact->CustomFields->c->identification_type->LookupName;
        $this->data["person_id"]                   = $datax->incident->PrimaryContact->ID;
        $this->data["person_commune"]              = $datax->incident->PrimaryContact->CustomFields->RPC->Comuna->LookupName;
        $this->data["partes_con_valor"]            = explode(",", $datax->incident->CustomFields->c->production_comments);
        $banks                                     = $this->CI->rcModel->getAllBanks();
        $bankAccountTypes                          = $this->CI->rcModel->getBankAccountTypes();
        $liquidationType                           = $this->CI->rcModel->getCompensationTypes();


        foreach ($banks as $key => $lineBusiness)
        {
            $a_lineOfBusiness["name"]     = $lineBusiness->Name;
            $a_lineOfBusiness["ID"]       = $lineBusiness->ID;
            $this->data['js']['bancos'][] = $a_lineOfBusiness;
        }

        foreach ($bankAccountTypes as $key => $lineBusiness)
        {
            $a_lineOfBusiness["name"]     = $lineBusiness->Name;
            $a_lineOfBusiness["ID"]       = $lineBusiness->ID;
            if($lineBusiness->ID == 1)
            {
              $this->data['js']['bank_account_type'][] = $a_lineOfBusiness; //TODO IN PROD : REPARAR PARA ACEPTAR OTROS TIPOS DE CUENTA BANCARIA
            }
        }

        $a_lineOfBusiness["name"]     = "Seleccione...";
        $a_lineOfBusiness["ID"]       = 0;
        $this->data['js']['liquidation_type'][] = $a_lineOfBusiness;

        foreach ($liquidationType as $key => $lineBusiness)
        { 
          if ($lineBusiness->ID === 2)
            continue;
          $a_lineOfBusiness["name"]     = $lineBusiness->Name;
          $a_lineOfBusiness["ID"]       = $lineBusiness->ID;
          $this->data['js']['liquidation_type'][] = $a_lineOfBusiness;
        }
        return parent::getData();
      }
      else
      {
        header('Location: '.$this->error_page);
      }
    }
    else
    {
      header('Location: '.$this->error_page);
    }

    
  }

  /**
   * Este metodo captura los datos del JWT
   * y los prepara para su utilizacion
   *
   * @access public
   * @param String clave encriptada JWT
   * @return Object
 */
  function setAtributes($param)
  {
    $atributes = $this->CI->rcModel->decodeJWT( $param , $this->key , $this->alg);
    if($atributes == false)
    {
      header('Location: '.$this->error_page);
    }
    $data = $atributes->data;
    if($atributes->exp < time())
    {
      $expired = 1;
    }
    else
    {
      $expired = 0;
    }
    $incident_ref_numb       = $data->incident;
    $this->incident_ref_numb = $incident_ref_numb;
    $_data                   = new \stdClass();
    $_data->exp              = $atributes      ->exp;
    $_data->iat              = $atributes      ->iat;
    $_data->incident_ref     = $incident_ref_numb;
    $_data->incident         = $incident;
    $_data->isExpired        = $expired;
    $_data->incident         = RNCPHP\Incident::first("ReferenceNumber = '{$incident_ref_numb}' ");

    return $_data;
  }
  
   /**
     * Ajax de cambio de estado y adjunto de archivos a Incident
     * @access public
     * @param Object Datos perfilados desde JWT
     * @return Response
    */
  function handle_default_ajax_endpoint($params)
  {
    try
    {
      \RightNow\Connect\v1_2\ConnectAPI::commit();

      $param         = json_decode($params['data'], true);
      
      $person_mobile = $param["person_mobile"];
      $person_email  = $param["person_email"];
      $person_id     = $param["person_id"];
      $incident_id   = $param["incident"];
      $a_file        = $param["files"];
      $contact       = $this->CI->ContactGeneral->getContactByID($person_id);

      if($contact === FALSE)
      {
        throw new \Exception("Error {$this->CI->ContactGeneral->getLastError()}", 1);
      }
      
      if($contact->Emails[0]->Address != $person_email || $contact->Phones[0]->Number != $person_mobile)
      {
        $contact->Phones[0]->Number = $person_mobile;

        if($contact->Emails[0]->Address != $person_email)
        {
          $contact->Emails[0]->Address = $person_email;
        }

        $contact->save();
      }

      $ultimate_incident  = $this->CI->rcModel->getIncidentByID($incident_id);
      $incident_id        = $this->CI->rcModel->joinFile2Incident($incident_id, $a_file);
      if($ultimate_incident->CustomFields->c->vdr)
      {
        $ultimate_incident->StatusWithType->Status->ID = 123; //En Revisión
      }else{
        $ultimate_incident->StatusWithType->Status->ID = 177; //Estudio de cobertura
      }

      $ultimate_incident->save();

      if(!$incident_id)
      {
        throw new \Exception("Error {$this->CI->rcModel->getLastError()}", 1);
      }
      else
      {
        // Eliminar archivos
        $is_correct = FALSE;
        foreach($a_file as $file)
        {
          $result = $this->CI->Files->deleteFileByUniqueId($file["uniqueName"]);
          if($result === FALSE)
            throw new Exception("Error eliminando archivo {$file['fileName']}", 1);
        }
      }
      
      $response              = new \stdClass();
      $response->success     = TRUE;
      $response->incident_id = $incident_id;
      $response->message     = "Documentos adjuntos correctamente";

      echo json_encode($response);
    }
    catch(\Exception $e)
    {
      \RightNow\Connect\v1_2\ConnectAPI::rollback();

      $response          = new \stdClass();
      $response->success = FALSE;
      $response->message = $e->getMessage();

      echo json_encode($response);
    }
  }

  function handle_update_normalized_docs_ajax_endpoint ($params)
  {
    $param              = json_decode($params['data'], true);
    $bank               = $param["transfer_selector"];
    $account_type       = $param["account_selector"];
    $account_number     = $param["nro_cta"];
    $compensation_type  = $param["compensation_type"];
    $person_mobile      = $param["person_mobile"];
    $person_email       = $param["person_email"];
    $person_id          = $param["person_id"];
    $incident_id        = $param["incident"];
    $a_file             = $param["files"];
    
    $contact            = $this->CI->ContactGeneral->getContactByID($person_id);

    if($contact === FALSE)
    {
      throw new \Exception("Error {$this->CI->ContactGeneral->getLastError()}", 1);
    }
    
    if($contact->Emails[0]->Address != $person_email || $contact->Phones[0]->Number != $person_mobile)
    {
      $contact->Phones[0]->Number = $person_mobile;
      if($contact->Emails[0]->Address != $person_email)
      {
        $contact->Emails[0]->Address = $person_email;
      }
      $contact->save();
    }

    $ultimate_incident  = $this->CI->rcModel->getIncidentByID($incident_id);
    $execute            = $this->CI->rcModel->saveResponse($incident_id,$bank,$account_type,$account_number,$contact->ID,$compensation_type);
    $incident_id        = $this->CI->rcModel->joinFile2Incident($incident_id, $a_file,3);
    $ultimate_incident->CustomFields->c->is_issue_right = 1;
    $ultimate_incident->save();
    if(!$incident_id || !$execute)
    {
      throw new \Exception("Error {$this->CI->rcModel->getLastError()}", 1);
    }
    else
    {
      // Eliminar archivos
      $is_correct = FALSE;

      foreach($a_file as $file)
      {
        $result = $this->CI->Files->deleteFileByUniqueId($file["uniqueName"]);
        if($result === FALSE)
          throw new Exception("Error eliminando archivo {$file['fileName']}", 1);
      }
    }
    
    $response              = new \stdClass();
    $response->success     = TRUE;
    $response->incident_id = $incident_id;
    $response->message     = "Documentos adjuntos correctamente";
    echo json_encode($response);
  }

  function handle_update_status_ajax_endpoint ($params)
  {
    $param          = json_decode($params['data'], true);
    $observation    = $param["observation"];
    $aproved        = $param["aproved"];
    $incident       = $param["incident_id"];
    $response           = new \stdClass();
    if($aproved)
    {
      $observation        = "TERCERO ACEPTA PROPUESTA :".$observation;

      if($this->CI->rcModel->addResponseToTread($incident,$observation,163))
      {
        $response->success = true;
        $response->message = "exito";  
      }
      else
      {
        $response->success = false;
        $response->message = "error ->".$this->CI->rcModel->getLastError();
      }
    }
    else
    {
      $observation = "TERCERO RECHAZA CON COMENTARIO :".$observation;
      if($this->CI->rcModel->addResponseToTread($incident,$observation,188))// en evaluacion
      {
        $response->success = true;
        $response->message = "exito"; 
      }
      else
      {
        $response->success = false;
        $response->message = "error ->".$this->CI->rcModel->getLastError();
      }
    }
    echo json_encode($response);
    
  }
}