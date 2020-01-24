<?php
namespace Custom\Models;
use RightNow\Connect\v1_3 as RNCPHP;

class rcModel extends \RightNow\Models\Base
{
  public $key           = "4RC";
  public $alg           = array('HS256');
  public $errorMessage  = '';
  private $url_uploadFile = "http://apps4test.realechile.cl:8081/WSCarpetaDigital/subirDocumento";

  public function getLastError()
  {
    return $this->errorMessage;
  }

  function __construct()
  {
    parent::__construct();
    $this->CI->load->library('JWT3');
    $this->CI->load->helper('utils_helper');
    $this->CI->load->Model('custom/IncidentGeneral');
    $this->CI->load->model('custom/Files');
    $this->CI->load->model('custom/ConnectUrl');
  }

  function alreadyJWT($id)
  {
    try{
      $incident = RNCPHP\Incident::fetch($id);
      if($incident->CustomFields->c->text_generic_email)
      {
        return $incident->CustomFields->c->text_generic_email;
      }
      else
      {
        return "";
      }
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      $this->errorMessage = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return false;
    }
  }


  function bringEveryRCTicket()
  {
    try{
      $incident = RNCPHP\Incident::find('Category.ID = 247');
      return $incident;
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      $this->errorMessage = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return false;
    }
  }

  function setAtributes($param)
  {
    $atributes  = $this->decodeJWT( $param , $this->key , $this->alg );
    if($atributes)
    {
      $data       = $atributes->data;
      if($atributes->exp < time())
      {
          $expired = 1;
      }
      else
      {
          $expired = 0;
      }
      $incident_ref_numb          = $data->incident;
      $_data                      = new \stdClass();
      $_data->exp                 = $atributes            ->exp;
      $_data->iat                 = $atributes            ->iat;
      $_data->incident_ref        = $incident_ref_numb;
      $_data->incident            = $incident;
      $_data->isExpired           = $expired;
      $_data->incident            = RNCPHP\Incident::first("ReferenceNumber = '{$incident_ref_numb}' ");
      return $_data;
    }
    else
    {
      return false;
    }
  }

  function getBankAccountTypes()
  {
    try
    {
      $incident = RNCPHP\SQUADRA\BankAccountType::find("ID IS NOT NULL");
      return $incident;
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      $this->errorMessage = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return false;
    }
  }

  function getCompensationTypes()
  {
    try
    {
      $incident = RNCPHP\SQUADRA\CompensationType::find("ID IS NOT NULL");
      return $incident;
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      $this->errorMessage = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return false;
    }
  }

  function getAllBanks()
  {
    try
    {
      $obj = RNCPHP\SQUADRA\Bank::find("Enabled = 1 and IsChilean = 1");
      return $obj;
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      $this->errorMessage = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return false;
    }
  }

  function getIncidentByID($id)
  {
    try
    {
      $incident = RNCPHP\Incident::fetch($id);
      return $incident;
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      $this->errorMessage = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return false;
    }
  }

  function decodeJWT($param)
  {
    try
    {
      $atributes  = $this->CI->jwt3->decode( $param , $this->key , $this->alg );
      return $atributes;
    }
    catch( ExpiredException  $ex)
    {
      return false;
    }
    
  }

  public function attachFile($obj_id, $name, $uniqueName, $extension, $folder, $mimetype)
  {
    try
    {
      $this->file             = new RNCPHP\GENERIC\AttachmentsDocs();
      $this->file->UniqueName = $uniqueName;
      $this->file->Name       = $name.date('dmyhis');
      $this->file->Extension  = $extension;
      $this->file->mimetype   = $mimetype;
      $this->file->folder     = $folder;
      $this->file->Incident   = RNCPHP\Incident::fetch($obj_id);
      $this->file->Save();
      return $this->file;
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      $this->error['message'] = $err->getMessage();
      return false;
    }

    return true;
  }

  public function getFiles($obj_id)
  {
    try
    {
      $a_obj = RNCPHP\GENERIC\AttachmentsDocs::find("Incident.ID = $obj_id");
      return $a_obj;
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      $this->error['message'] = $err->getMessage();
      return false;
    }
  }

  public function joinFile2Incident($id, $a_file , $level = 1)
  {
    try
    {
      $incident                                 = RNCPHP\Incident::fetch($id);
      $incident->FileAttachments                = new RNCPHP\FileAttachmentIncidentArray();
      $digit = 0;
      foreach($a_file as $key => $file)
      {
        $uniqueName                = $file["uniqueName"];
        $obj_file                  = RNCPHP\GENERIC\AttachmentsDocs::first("UniqueName = '{$uniqueName}'");
        $a_name_extension          = explode(".", $obj_file->Name);
        $extension                 = end($a_name_extension);
        $file_name                 = reset($a_name_extension);
        if($level == 3)    
        {
          $obj_file->description     = "RESPUESTA TERCERO : Archivo firmado ante notario";
          $ultimate_name             = $file_name."_NOTARIADO".".".$extension;
        }
        else   
        {
          $ultimate_name             = $file_name.date('dmyhis').".".$extension;
        }        
        $obj_file->Name            = $ultimate_name;
        $obj_file->save();
        $file_path                   = $obj_file->Folder . "/" . $obj_file->UniqueName;
        $file_content                = file_get_contents($file_path);
        $fa                          = new RNCPHP\FileAttachmentIncident;
        $fp                          = $fa->makeFile();
        fwrite($fp, $file_content);
        fclose($fp);

        $fa->ContentType             = $obj_file->MimeType;
        $fa->FileName                = $obj_file->Name;
        $incident->FileAttachments[] = $fa;
        $digit = $digit +1;
      }

      $incident->Save();

      return $incident->ID;
    }
    catch (\Exception $err)
    {
      $this->errorMessage  = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return FALSE;
    }
  }

  public function saveURLwithIncident($token, $incident_id)
  {
    try
    {
      $incident = RNCPHP\Incident::fetch($incident_id);
      $incident->CustomFields->c->text_generic_email = $token;
      $incident->save();
      return true;
    }
    catch(\Exception $err)
    {
      $this->errorMessage  = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return FALSE;
    }
    
  }

  public function createRCIncident($father,$plate,$desc)
  {
    try{
      $father_inc                                         = $this->getIncidentByID($father);
      $incident                                           = new RNCPHP\Incident();
      $incident->StatusWithType->Status->ID               = 104;
      $incident->CustomFields->REALE->Request             = $father_inc;
      $incident->CustomFields->REALE->request_description = $desc;
      $incident->CustomFields->c->vehicle_plate           = $plate;
      $incident->Category->ID                             = 247;
      $incident->Product->ID                              = 4;
      $incident->save();
      return true;
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      $this->error['message'] = $err->getMessage();
      return false;
    }
  }

  public function saveResponse($incident_id,$bank,$account_type,$account_number,$contact_id,$compensation_type)
  {
    try
    {
      $incident                                       = RNCPHP\Incident::fetch($incident_id);
      $contact                                        = RNCPHP\Contact::fetch($contact_id);
      $PaymentMethod                                  = new RNCPHP\SQUADRA\PaymentMethod();
      $PaymentMethod->Rut                             = $contact->CustomFields->c->rut."-". $contact->CustomFields->c->dv;
      $PaymentMethod->CompensationType                = RNCPHP\SQUADRA\CompensationType::fetch($compensation_type);
      $PaymentMethod->OwnerName                       = $contact->Name->First." ".$contact->Name->Last;
      if($compensation_type == 1) // Tipo de Pago : transferencia
      {
        $PaymentMethod->Bank                          = RNCPHP\SQUADRA\Bank::fetch($bank);                    // Banco
        $PaymentMethod->CurrentAccountNumber          = $account_number;                                      // Cuenta Corriente
        $PaymentMethod->BankAccountType               = RNCPHP\SQUADRA\BankAccountType::fetch($account_type); // Tipo de cuenta
      }
      $PaymentMethod->save();
      $incident->CustomFields->SQUADRA->PaymentMethod = $PaymentMethod;   // Relación con el incidente
      $incident->StatusWithType->Status->ID           = 161; //evaluacion de documentacion
      $incident->save();
      return true;
    }
    catch(\Exception $err)
    {
      $this->errorMessage  = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return false;
    }
  }

  public function returnTextGenericEmail($incident_id)
  {
    try
    {
      $incident = RNCPHP\Incident::fetch($incident_id);
      return $incident->CustomFields->c->text_generic_email;
    }
    catch(\Exception $err)
    {
      $this->errorMessage = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return FALSE;
    }
  }

  public function addResponseToTread($incident_id,$third_response,$status)
  {
    try
    {
      $incident = RNCPHP\Incident::fetch($incident_id);;
      $this->CI->IncidentGeneral->insertPrivateNote($incident_id,$third_response,true);
      $incident->StatusWithType->Status->ID = $status;
      if($status == 152)
      {
        $incident->CustomFields->c->send_date = true; 
      }else if($status == 188)
      {
        $incident->CustomFields->c->unread = true;
      }
      $incident->CustomFields->c->send_date = true;
      $incident->save();
      return true;
    }
    catch(\Exception $err)
    {
      $this->errorMessage = "Codigo : " . $err->getCode() . " " . $err->getMessage();
      return FALSE;
    }
  }

}
?>