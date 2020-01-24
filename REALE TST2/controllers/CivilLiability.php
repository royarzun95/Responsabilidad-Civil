<?php

namespace Custom\Controllers;
use RightNow\Connect\v1_3 as RNCPHP;

class CivilLiability extends \RightNow\Controllers\Base
{
  public $URL_GET_IMAGE_NAME = "http://boldo.realechile.cl/ws_obtener_nombre_imagen.asp"; // URL Boreal Test
  public $URL_UPLOAD_IMAGE   = "http://boldo.realechile.cl/php/file-echo-canvas.php";
  public $URL_REST_API       = "https://reale--tst2.custhelp.com/services/rest/connect/v1.3/incidents";
  public $lastError          = '';
  public $URL                = "http://apps4test.realechile.cl:8081/WSCrearTramitacion/altaTramitacion"; // Test
  //CONST URL          = "http://apps4.realechile.cl:8081/WSCrearTramitacion/altaTramitacion"; // Prod
  public function __construct()
  {
    parent::__construct();

    $this->load->helper('utils_helper');
    $this->load->model('custom/IncidentGeneral');
    $this->load->model('custom/rcModel');
    $this->load->model('custom/ConnectUrl');
    $this->load->library('JWT2');
  }

  /**
   * Servicio para capturar la indemnización y cotización enviada desde boreal.
   * @param JSON
   */
  public function compensationOffer()
  {
    try
    {
      // Make sure that it is a POST request.
      if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0)
      {
        throw new \Exception('Request method must be POST!', 3);
      }

      // Make sure that the content type of the POST request has been set to application/json
      $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
      if (strcasecmp($contentType, 'application/json') != 0)
      {
        throw new \Exception('Content type must be: application/json', 4);
      }

      // Receive the RAW post data.
      $content = trim(file_get_contents("php://input"));

      // Attempt to decode the incoming RAW post data from JSON.
      $decoded = json_decode($content, true);

      // If json_decode failed, the JSON is invalid.
      if (!is_array($decoded))
      {
        throw new \Exception('Received content contained invalid JSON!', 5);
      }
      else
      {
        $reference_number = $decoded["id_crm"];
        $amount           = $decoded["monto"];
        $parts            = $decoded["parts"];

        if(is_null($reference_number) || is_null($amount) || is_null($parts))
          throw new \Exception("Todos los valores son obligatorios", 6);
        
        if(strlen($reference_number) < 13 || count(explode("-", $reference_number)) < 2)
          throw new \Exception("El valor de 'id_crm' debe tener el formato XXXXXX-000001.", 6);
        
        
        $incident = $this->IncidentGeneral->get($reference_number);
        if(!$incident)
          throw new \Exception($this->IncidentGeneral->getError(), 6);
        
        if($incident->Product->ID !== 4 || $incident->Category->ID !== 247) // Producto ID 4 = Siniestro, Categoría ID 247 = Responsabilidad Civil
          throw new \Exception("El requerimiento no corresponde a un ticket de RC.", 7);

        if(intval($amount) === 0)
          throw new \Exception("El valor de 'monto' debe ser numérico.", 6);
        
        $this->IncidentGeneral->insertPrivateNote($incident->ID, "'compensationOffer'. JSON recibido {$content}", TRUE);
        $this->makeJWT($incident->ID);
        //$incident->CustomFields->REALE->generic_email_text  = $jwt;  // @royarzun says : DEPRECATED
        $incident->CustomFields->c->net_amount              = (int) $amount;
        $incident->CustomFields->c->production_comments     = $parts;
        $incident->CustomFields->c->unread = false;
        $incident->StatusWithType->Status->ID               = 178; // Estado: En Negociación
        $incident->Save();
        header('Content-Type: application/json');
        $a_result = array("result"=> TRUE,  "response" => array("message" => "Propuesta de indemnización cargada con éxito."));
        echo json_encode($a_result);
      }
    }
    catch (\Exception $e)
    {
      header('Content-Type: application/json');
      $a_result = array("result"=> FALSE,  "error" => array("code"=> $e->getCode(), "message" => $e->getMessage()));
      echo json_encode($a_result);
    }
  }

  function convertTime($month,$day,$hour,$minute)
  {
    $segundos   = 1;
    $minutos    = 60;
    $horas      = 3600;
    $dias       = 86400;
    $mes        = $month * $dias * 31;
    $calculated = ($month * $mes) + ($day * $dias) + ($hour * $horas) + ($minute * $minutos);
    
    return $calculated;
  }

  function makeJWT($incident_idx)
  {
    $incident_id            = $incident_idx;
    $global_incident        = $this->rcModel->getIncidentByID($incident_id);
    $keyStoreData           = array("incident"  => $global_incident->ReferenceNumber,"count" => 1);
    $iat_time               = time();
    $exp_time               = $this->convertTime(0,30,24,0);

    $global_remaining_time  = $iat_time + $exp_time ;
    $token = array(
      'iat'  => $iat_time,
      'exp'  => $iat_time + $exp_time,
      'data' => $keyStoreData
    );

    $jwt        = $this->jwt2->encode($token, "4RC", "HS256");
    $this->rcModel->saveURLwithIncident($jwt,$incident_id);
    return true;
  }

  /**
   * Servicio para informar del pago de la orden en CRM y cerrar el ticket de RC.
   * @param JSON
   */
  public function paymentOrderNotice()
  {
    try
    {
      // Make sure that it is a POST request.
      if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0)
      {
        throw new \Exception('Request method must be POST!', 3);
      }

      // Make sure that the content type of the POST request has been set to application/json
      $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
      if (strcasecmp($contentType, 'application/json') != 0)
      {
        throw new \Exception('Content type must be: application/json', 4);
      }

      // Receive the RAW post data.
      $content = trim(file_get_contents("php://input"));

      // Attempt to decode the incoming RAW post data from JSON.
      $decoded = json_decode($content, true);

      // If json_decode failed, the JSON is invalid.
      if(!is_array($decoded))
      {
        throw new \Exception('Received content contained invalid JSON!', 5);
      }
      
      // $id_crm        = $decoded["id_crm"];   "id_crm": "200107-000012",
      // $created_date  = $decoded["created_date"];
      $nsinies       = $decoded["nsinies"];
      $iaxis_id      = $decoded["iaxis_id"];
      $sinister_id   = $decoded["sinister_id"];
      $n_tramit      = $decoded["n_tramit"];
      $boreal_id     = $decoded["boreal_id"];
      $amount        = $decoded["amount"];
      $emission_date = $decoded["emission_date"];
      // $order_type    = $decoded["order_type"];
      $status_id     = $decoded["status_id"];
      $a_beneficiary = $decoded["beneficiary"];

      // if(is_null($iaxis_id) || is_null($sinister_id) || is_null($n_tramit) || is_null($boreal_id) || is_null($amount) || is_null($created_date) || is_null($emission_date)|| is_null($order_type) || is_null($status_id))
      //   throw new \Exception("Todos los valores son obligatorios", 6);
      
      if(is_null($iaxis_id) || is_null($sinister_id) || is_null($n_tramit) || is_null($boreal_id) || is_null($amount)  || is_null($emission_date)||  is_null($status_id))
        throw new \Exception("Todos los valores son obligatorios", 6);
      $incident = $this->IncidentGeneral->getIncidentByQuery("CustomFields.c.claim_number ='{$nsinies}' AND CustomFields.c.process_number = {$n_tramit}");
      if($incident->CustomFields->SQUADRA->PaymentMethod->CompensationType == 1)
      {
        if(is_null($a_beneficiary["rut"]) || is_null($a_beneficiary["dv"]) || is_null($a_beneficiary["account_type"]) || is_null($a_beneficiary["checking_account"]) || is_null($a_beneficiary["payment_type"]) || is_null($a_beneficiary["bank"]) || is_null($a_beneficiary["name"]))
        throw new \Exception("Todos los valores son obligatorios", 6);
      }
      
      if(!$incident)
        throw new \Exception($this->IncidentGeneral->getError(), 6);
      
      if($incident->Product->ID !== 4 || $incident->Category->ID !== 247) // Producto ID 4 = Siniestro, Categoría ID 247 = Responsabilidad Civil
        throw new \Exception("El requerimiento no corresponde a un ticket de RC.", 7);
      
      if($status_id == 1)//pagado
      {
        $this->IncidentGeneral->insertPrivateNote($incident->ID, "'paymentOrderNotice'. JSON recibido {$content}", TRUE);
        $incident->CustomFields->c->net_amount          = $amount;        // long
        $incident->CustomFields->c->issue_date          = $emission_date; //long
        $incident->CustomFields->c->payment_registered  = 1;
        $incident->StatusWithType->Status->ID           = 108; // Estado: Solucionado
        $incident->CustomFields->c->sento_acrback       = 1;
        $incident->CustomFields->c->sento_acrfront      = 1;
        $incident->Save();
        $a_result = array("result"=> TRUE,  "response" => array("incident" => $incident->ReferenceNumber, "state" => TRUE, "message" => "Pago informado exitosamente."));
      }
      else
      {
        $this->IncidentGeneral->insertPrivateNote($incident->ID, "'paymentOrderNotice'. JSON recibido {$content}", TRUE);
        $this->IncidentGeneral->insertPrivateNote($incident->ID, "'paymentOrderNotice'. No se ha recibido el pago debido a un error en iaxis {$content}", TRUE);
        $a_result = array("result"=> TRUE,  "response" => array("incident" => $incident->ReferenceNumber, "state" => FALSE, "message" => "Fallo en el pago informado a liquidador."));
      }
      header('Content-Type: application/json');
      echo json_encode($a_result);
    }
    catch (\Exception $e)
    {
      header('Content-Type: application/json');
      $a_result = array("result"=> FALSE,  "error" => array("code"=> $e->getCode(), "message" => $e->getMessage()));
      echo json_encode($a_result);
    }
  }

  public function uploadFilesToBoreal()//$incident_reference
  {
    try
    {
      // Make sure that it is a POST request.
      if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0)
      {
        throw new \Exception('Request method must be POST!', 3);
      }

      // Make sure that the content type of the POST request has been set to application/json
      $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
      if (strcasecmp($contentType, 'application/json') != 0)
      {
        throw new \Exception('Content type must be: application/json', 4);
      }

      // Receive the RAW post data.
      $content = trim(file_get_contents("php://input"));

      // Attempt to decode the incoming RAW post data from JSON.
      $decoded = json_decode($content, true);

      // If json_decode failed, the JSON is invalid.
      if (!is_array($decoded))
      {
        throw new \Exception('Received content contained invalid JSON!', 5);
      }
      else
      {
        $reference_number  = $decoded["id_crm"];
        $incident          = $this->IncidentGeneral->get($reference_number);
        $a_extensions      = array("jpg","jpeg", "png");
        $a_imagesBoreal    = array();
        $q_fileAttachments = count($incident->FileAttachments);

        // Si existen archivos adjuntos
        if($q_fileAttachments > 0)
        {
          for($i = 0; $i < $q_fileAttachments; $i++)
          {
            $a_fileName = explode('.', $incident->FileAttachments[$i]->FileName);
            $sure_ext = $a_fileName[1];
            if(in_array($sure_ext, $a_extensions))
            {
              // Se guardar los que tengan la extensión correspondiente a imagen
              $a_imagesBoreal[$incident->FileAttachments[$i]->ID] = $incident->FileAttachments[$i];
            }
            //$this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': Arreglo ". print_r($a_imagesBoreal, TRUE),true);
          }
        }

       

        $q_borealImages = count($a_imagesBoreal);
        // $json = json_encode($a_imagesBoreal);

        // $this->IncidentGeneral->insertPrivateNote($incident->ID, " count " . $q_borealImages ." Array de imagenes " . $json, true);
        // return;

        if($q_borealImages < 1)
        {
          $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': El requerimiento no tiene imágenes para subir a Boreal.",true);
          return;
        }
    
        $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': Se han encontrado {$q_borealImages} imágenes que serán subidas a Boreal.",true);
        $boreal_number = $incident->CustomFields->c->boreal_number;

        if(!$boreal_number)
        {
          $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': El requerimiento no tiene número de evaluación de daños Boreal.",true);
          return;
        }
        
        // Se setea la autenticación básica para el uso de Rest API
        $this->ConnectUrl->setAuthData("Integer1", "|r90E5a[Le?["); // Utilizar las credenciales de mandato en ambiente de producción

        // $this->IncidentGeneral->insertPrivateNote($incident->ID, "Imagenes': ". print_r($a_imagesBoreal, true), true);

        foreach ($a_imagesBoreal as $image)
        {
          /**
           * Se consumirá el servicio que obtiene el nombre del archivo
          */
          $a_request_fileName = array( "incidente2" => $boreal_number );
          
          // llamada para obtener ID de imagen
          $resp_fileName = $this->ConnectUrl->requestCURLByPost2($this->URL_GET_IMAGE_NAME, $a_request_fileName, TRUE);
        
          if($resp_fileName === FALSE)
          {
            $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal' Error Respuesta desde Boreal : ".$this->ConnectUrl->getResponseError(),true);
            continue;
          }


          $a_fileName = json_decode($resp_fileName, TRUE);
          //$a_contacts = $a_fileName["contactos"];

          if (!$a_fileName) 
          {
            $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': Json de respuesta Imagen Boreal con problemas [No pudo ser parseado]  " . $resp_fileName, true);
            continue;
          }

          if (empty($a_fileName["contactos"][0]["id"]))
          {
            $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': Json de respuesta Imagen Boreal con problemas   " . $resp_fileName, true);
            continue;
          }

          $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': ID de Imagen para Boreal  " . $a_fileName["contactos"][0]["id"], true);
          // Se obtienen las imagen de RN y suben a boreal indicandole el ID recien obtenido
          $this->upload_image($incident, $a_fileName["contactos"][0]["id"], array($image));
          
        }
        $incident->StatusWithType->Status->ID = 188;
        $incident->save();
        header('Content-Type: application/json');
        $a_result = array("result"=> TRUE,  "response" => array("message" => "deberian haberse cargado todo."));
        echo json_encode($a_result);
      }
    }
    catch (\Exception $e)
    {
      header('Content-Type: application/json');
      $a_result = array("result"=> FALSE,  "error" => array("code"=> $e->getCode(), "message" => $e->getMessage()));
      echo json_encode($a_result);
    }
   
  }



  /**
   * Subir archivo a Boreal
   *
   * @param int $id_image
   * @return bool true en caso de éxito o false en caso de falla.
   */
  public function upload_image($incident, $id_image, $a_imagesBoreal)
  {
    try 
    {
      $this->IncidentGeneral->insertPrivateNote($incident->ID, " Subiendo Nueva Imagen con ID {$id_image} ", true);
      //sleep(0.5);

      $url_target = $this->URL_REST_API . "/" . $incident->ID . "/fileAttachments";

      foreach($a_imagesBoreal as $image)
      {
        $url_image                 = $url_target . "/" . $image->ID;
        $file                      = $this->ConnectUrl->getDataFile($url_image);
        if ($file === false)
        {
          $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': Error Obteniendo archivo de API CRM {$url_image} " . $this->ConnectUrl->getResponseError(), true);
          continue;
        }

        $data_response             = json_decode($file, TRUE);
        $a_fileData["data"]        = $data_response["data"];
        $a_fileData["contentType"] = $image->ContentType;
        $a_fileData["fileName"]    = date('Ymdhis',time()). "_" . $image->FileName;

        $a_data = array(
          "nombre" => $id_image,
          "img" => NULL
        );


        $response = $this->ConnectUrl->requestCURLByPostWithFiles2($this->URL_UPLOAD_IMAGE, $a_data, $a_fileData, FALSE);
        if($response === FALSE)
        {
          $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': Error desde Curl al intentar subir la imagen {$image->FileName} a Boreal. ".$this->ConnectUrl->getResponseError(),true);
          continue;
        }

        $this->IncidentGeneral->insertPrivateNote($incident->ID, "'UploadImagesToBoreal': Imagen {$image->FileName} subida a Boreal exitosamente. Respuesta de Boreal: " . $response,true);
      }
      return TRUE;
    } 
    catch (\Exception $e) 
    {
      $this->IncidentGeneral->insertPrivateNote($incident->ID, "CPM UploadImagesToBoreal: " . $e->getMessage(),true);
    }
  }

  public function sendCreateTramitRequest()
  {

    // Make sure that it is a POST request.
    if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0)
    {
      throw new \Exception('Request method must be POST!', 3);
    }

    // Make sure that the content type of the POST request has been set to application/json
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if (strcasecmp($contentType, 'application/json') != 0)
    {
      throw new \Exception('Content type must be: application/json', 4);
    }

    // Receive the RAW post data.
    $content = trim(file_get_contents("php://input"));

    // Attempt to decode the incoming RAW post data from JSON.
    $decoded = json_decode($content, true);

    // If json_decode failed, the JSON is invalid.
    if (!is_array($decoded))
    {
      throw new \Exception('Received content contained invalid JSON!', 5);
    }
    else
    {
      $reference_number  = $decoded["id_crm"];
      $incident          = $this->IncidentGeneral->get($reference_number);
      $father            = $this->IncidentGeneral->get($incident->CustomFields->REALE->Request->ID);
      $jsonDataEncoded   = json_encode($decoded["data_fields"]);
      $result = $this->ConnectUrl->requestCURLJsonRaw($this->URL, $jsonDataEncoded);

      if ($result === false)
      {
        $this->IncidentGeneral->insertPrivateNote($incident->ID, "RC_CreateTramit. Error interno de CRM: ".$this->ConnectUrl->getResponseError()." ".$result);
        $incident->CustomFields->c->process_number = null;
        $incident->CustomFields->c->claim_number   = null;
        $incident->Save(RNCPHP\RNObject::SuppressAll);
      }
      else
      {
        $a_result = json_decode($result, true); 
        $n_tramit = $a_result["numeroTramitacion"];
        $incident->CustomFields->c->process_number = $n_tramit; // Número de tramitación 
        $incident->CustomFields->c->claim_number   = $father->CustomFields->c->claim_number;
        $this->IncidentGeneral->insertPrivateNote($incident->ID, "numero de tramitacion ".$n_tramit);
        $incident->Save();
      }
    header('Content-Type: application/json');
    echo json_encode($result);
  }
  }

}
