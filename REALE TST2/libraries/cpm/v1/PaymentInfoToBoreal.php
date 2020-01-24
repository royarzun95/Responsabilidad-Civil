<?php

namespace Custom\Libraries\CPM\v1;
use RightNow\Connect\v1_3 as RNCPHP;

 require 'ConnectUrl.php';

class PaymentInfoToBoreal
{
  CONST URL_BOREAL = "http://boldo.realechile.cl/ws_datos_pago.asp"; // URL Boreal Test

  public static function execute($runMode, $action, $incident, $cycle)
  {
    try
    {
      $a_request = array(
        "Datos" => array(
          "tipo_pago"          => $incident->CustomFields->SQUADRA->PaymentMethod->CompensationType->id_iaxis,
          "banco"              => $incident->CustomFields->SQUADRA->PaymentMethod->Bank->id_iaxis,
          "nro_cuenta"         => $incident->CustomFields->SQUADRA->PaymentMethod->CurrentAccountNumber,
          "rut"                => $incident->PrimaryContact->CustomFields->c->rut,
          "rut_dv"             => $incident->PrimaryContact->CustomFields->c->dv,
          "nombre"             => (explode(" ", $incident->PrimaryContact->Name->First )[0]) ? explode(" ", $incident->PrimaryContact->Name->First )[0] : "",
          "nombre2"            => (explode(" ", $incident->PrimaryContact->Name->First )[1]) ? explode(" ", $incident->PrimaryContact->Name->First )[1] : "",
          "apellidopaterno"    => (explode(" ", $incident->PrimaryContact->Name->Last  )[0]) ? explode(" ", $incident->PrimaryContact->Name->Last  )[0] : "",
          "apellidomaterno"    => (explode(" ", $incident->PrimaryContact->Name->Last  )[1]) ? explode(" ", $incident->PrimaryContact->Name->Last  )[1] : "",
          "sexo"               => $incident->PrimaryContact->CustomFields->c->gender[0],
          "email"              => $incident->PrimaryContact->Emails[0]->Address,
          "id_evaluacion"      => $incident->CustomFields->c->boreal_number
        )
      );
      $json_request = json_encode($a_request);

      self::insertPrivateNote($incident, "CPM PaymentInfoToBoreal: JSON enviado a Boreal {$json_request}");
      //ConnectUrl::setAuthData("Integer1", "|r90E5a[Le?[");
      //$resp_fileName = ConnectUrl::requestCURLByPost(self::URL_BOREAL, $json_request, TRUE);
      $resp_fileName = ConnectUrl::requestCURLJsonRaw(self::URL_BOREAL, $json_request); // Roberto, esta era la integración real
      // $resp_fileName = '{"codigo":0,"message":"Exito"}';
      self::insertPrivateNote($incident, "CPM PaymentInfoToBoreal: Respuesta de Boreal {$resp_fileName}");
      
      if($resp_fileName === FALSE)
        {
          self::insertPrivateNote($incident, "'Respuesta CPM PaymentInfoToBoreal': ". ConnectUrl::getResponseError());
          return;
        }
      $incident->StatusWithType->Status->ID = 187; // Estado: Pendiente de Orden de Indemnización
      $incident->Save();

    }
    catch (RNCPHP\ConnectAPIError $err) 
    {
      self::insertPrivateNote($incident, "CPM PaymentInfoToBoreal: " . $err->getMessage());
    }
  }

  public static function insertPrivateNote($incident, $textoNP)
  {
    try
    {
      $incident->Threads                   = new RNCPHP\ThreadArray();
      $incident->Threads[0]                = new RNCPHP\Thread();
      $incident->Threads[0]->EntryType     = new RNCPHP\NamedIDOptList();
      $incident->Threads[0]->EntryType->ID = 8; // 1: nota privada
      $incident->Threads[0]->Text          = $textoNP;
      $incident->Save(RNCPHP\RNObject::SuppressAll);
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      return FALSE;
    }
  }
}
