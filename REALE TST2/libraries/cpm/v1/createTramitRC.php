<?php

namespace Custom\Libraries\CPM\v1;
use RightNow\Connect\v1_3 as RNCPHP;

require 'ConnectUrl.php';

class createTramitRC
{
  CONST URL               = "http://apps4test.realechile.cl:8081/WSCrearTramitacion/altaTramitacion"; // Test
  CONST URL_TO_CONTROLLER = "http://reale--tst2.custhelp.com/cc/CivilLiability/sendCreateTramitRequest"; // Test
  
  //CONST URL          = "http://apps4.realechile.cl:8081/WSCrearTramitacion/altaTramitacion"; // Prod

  public static function execute($runMode, $action, $incident, $cycle)
  {
    try
    {
      self::insertPrivateNote($incident, "En CPM createTramitRC");
      $father = RNCPHP\Incident::fetch($incident->CustomFields->REALE->Request->ID);
      self::insertPrivateNote($incident, "encontrado padre -> ".$father->ReferenceNumber);
      self::insertPrivateNote($incident, "encontrado Nro de siniestro padre  -> ".$father->CustomFields->c->claim_number);
      $incident->CustomFields->c->claim_number = $father->CustomFields->c->claim_number;
      $incident->save();
      //Lógica para ver si obtenemos el código de marca en base al nombre ingresado en texto   
      $code_brand = null;
      if (!empty($incident->CustomFields->c->vehicle_brand))
      {
        $a_brands = RNCPHP\REALE\VehicleBrand::find("Name like '%".$incident->CustomFields->c->vehicle_brand."%'");   
        if (count($a_brands) > 0)
        {
          $code_brand = $a_brands[0]->CMARCA;
        }
      }
     
      $a_request_fields = array(
        "conductor" => array(
            "apellidoMaterno"       => null, 
            "apellidoPaterno"       => null,
            "digitov"               => 0,
            "email"                 => null,
            "fechaNacimiento"       => null,
            "nombre"                => "",
            "numide"                => null,
            "pcpais"                => null,
            "telefonoCel"           => null,
            "telefonoLocal"         => 0,
            "tipoLicencia"          => null,
            "localidad"             => null),
        "nsinies"                   => ($father->CustomFields->c->claim_number) ? $father->CustomFields->c->claim_number : "",           // Número de sniestro del ticket padre
        "tramitaciones"             => array(
            "cestra"                => 0,                                                // Abierto (valor en duro)
            "cgarant"               => null, 
            "ctipgas"               => null,
            "ctiptra"               => 2,                                               // Tercero
            "ctramitad"             => ($incident->CustomFields->REALE->Liquidador->CustomFields->c->liquidator_code) ? $incident->CustomFields->REALE->Liquidador->CustomFields->c->liquidator_code : "",   // código del liquidador <-- en caso de existir (debería ser null)
            "descripcion"           => null, 
            "pchasis"               => "",  
            "pcilindraje"           => "",
            "pcmarca"               => ($code_brand) ? $code_brand : "",       // marca código
            "pcmatric"              => ($incident->CustomFields->c->vehicle_plate)  ? $incident->CustomFields->c->vehicle_plate : "",       // Patente
            "pcmodelo"              => "",                                            // código modelo
            "pcodmotor"             => "",   
            "pctcausin_terc"        => 1,                                               //valor en duro
            "pcversion"             => "",
            "pnanyo"                => ($incident->CustomFields->c->fabrication_year) ? $incident->CustomFields->c->fabrication_year : null,
            "tercero"               => array(                                           //Datos conectado (si no es generico)
                "apellidoPaterno"   => (explode(" ", $incident->PrimaryContact->Name->Last  )[0]) ? explode(" ", $incident->PrimaryContact->Name->Last  )[0] : "",
                "apellidoMaterno"   => (explode(" ", $incident->PrimaryContact->Name->Last  )[1]) ? explode(" ", $incident->PrimaryContact->Name->Last  )[1] : "",
                "digitov"           => intval(($incident->PrimaryContact->CustomFields->c->dv)           ? $incident->PrimaryContact->CustomFields->c->dv    : null),                    
                "email"             => ($incident->PrimaryContact->Emails[0]->Address)            ? $incident->PrimaryContact->Emails[0]->Address     : "",                     
                "fechaNacimiento"   => null,                                                              
                "nombre"            => ($incident->PrimaryContact->Name->First)                   ? ($incident->PrimaryContact->Name->First)          : "" ,
                "numide"            => ($incident->PrimaryContact->CustomFields->c->rut)          ? ($incident->PrimaryContact->CustomFields->c->rut) : "" ,
                "pcpais"            => 152, 
                "telefonoCel"       => ($incident->PrimaryContact->Phones[0]->Number)             ? ($incident->PrimaryContact->Phones[0]->Number)    : null,
                "telefonoLocal"     => 0,
                "tipoLicencia"      => null,
                "localidad"         => null
            )
        )
      );
      
      // $a_request = array();
      $jsonDataEncoded = json_encode($a_request_fields);
      self::insertPrivateNote($incident, "RC_CreateTramit. Json Enviado: ".$jsonDataEncoded);
      header('Content-Type: application/json');
      $a_fields = array(
        "id_crm"      => $incident->ID, 
        "data_fields" => $a_request_fields
                      );
      // $result = ConnectUrl::requestCURLJsonRaw(self::URL, $jsonDataEncoded);
      $fields = json_encode($a_fields);
      $result = ConnectUrl::requestCURLJsonRaw(self::URL_TO_CONTROLLER, $fields);
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      self::insertPrivateNote($incident, "RC_CreateTramit. " . $err->getMessage() . ", línea " . $err->getLine());
    }
  }

  public static function insertPrivateNote($incident, $textoNP)
  {
    try
    {
      $incident->Threads = new RNCPHP\ThreadArray();
      $incident->Threads[0] = new RNCPHP\Thread();
      $incident->Threads[0]->EntryType = new RNCPHP\NamedIDOptList();
      $incident->Threads[0]->EntryType->ID = 8; // 1: nota privada
      $incident->Threads[0]->Text = $textoNP;
      $incident->Save(RNCPHP\RNObject::SuppressAll);
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      return false;
    }
  }
}
