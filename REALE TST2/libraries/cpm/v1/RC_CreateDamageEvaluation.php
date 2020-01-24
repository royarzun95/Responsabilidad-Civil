<?php

namespace Custom\Libraries\CPM\v1;
use RightNow\Connect\v1_3 as RNCPHP;

require 'ConnectUrl.php';

class RC_CreateDamageEvaluation
{
  CONST URL          = "https://boldo.realechile.cl/ws_crear_evaluacion.asp"; // Test
  //CONST URL          = "https://roble.realechile.cl/ws_crear_evaluacion.asp"; // Prod

  public static function execute($runMode, $action, $incident, $cycle)
  {
      try
      {
        if($incident->CustomFields->REALE->Request)
        {
          $parent_incident = $incident->CustomFields->REALE->Request;

          $declaration = RNCPHP\REALE\DeclarationDetail::fetch($parent_incident->CustomFields->REALE->DeclarationDetail->ID);

          if (!empty($incident->CustomFields->REALE->Organization->ID))
            $subsidary          = RNCPHP\Organization::fetch($incident->CustomFields->REALE->Organization->ID); //Taller
          if (!empty($incident->CustomFields->DECLARATION->LiquidatorOrg->ID))
            $liquidator_org     = RNCPHP\Organization::fetch($incident->CustomFields->DECLARATION->LiquidatorOrg->ID); //Liquidador Taller
          if (!empty($incident->CustomFields->REALE->Liquidador->ID))
            $liquidator_account = RNCPHP\Account::fetch($incident->CustomFields->REALE->Liquidador->ID); //liquidador Account

          if (!empty($liquidator_org))
          {
            $liquidator['ID']   = $liquidator_org->ID;
            $liquidator['name'] = $liquidator_org->Name;
            $liquidator['rut']  = $liquidator_org->CustomFields->c->rut;
          }
          else
          {
            $liquidator['ID']   = $liquidator_account->ID;
            $liquidator['name'] = $liquidator_account->Name->First." ".$liquidator_account->Name->Last;
            $liquidator['rut']  = $liquidator_account->CustomFields->c->rut;
            $rut_clean          = preg_replace('/[.-]/','',$liquidator['rut']);
            $liquidator['rut']  = substr($rut_clean, 0, strlen($rut_clean)-1);
          }

          // $objBrand = RNCPHP\REALE\VehicleBrand::fetch($incident->CustomFields->REALE->DriverPerson->VehicleBrand->ID);


          if (!empty($declaration->warranty_code))
          {
            $warranty = RNCPHP\DECLARATION\Warranty::first("COD_IAXIS = '$declaration->warranty_code'");
          }

          /* switch ($declaration->liquidation_type->ID)
          {
            case 1://1 "Domicilio"
              $addressEvaluation = $declaration->liquidation_address;
              $placeEvaluation   = $declaration->liquidation_type->LookupName;
              break;
            case 2://2 "Express"
              $addressEvaluation = $subsidary->CustomFields->c->address;
              $placeEvaluation   = "Sucursal";
              break;
            case 3://3 "In Situ"
              $addressEvaluation = $subsidary->CustomFields->c->address;
              $placeEvaluation   = $declaration->liquidation_type->LookupName;
              break;
            case 4://4 "Terreno"
              $addressEvaluation = $declaration->liquidation_address;
              $placeEvaluation   = "Domicilio";
              break;
            case 6: //6 "Remoto"
              $addressEvaluation = $subsidary->CustomFields->c->address;
              $placeEvaluation   = $declaration->liquidation_type->LookupName;
              break;
            case 7://7 "RT"
              $addressEvaluation = "";
              $placeEvaluation   = $declaration->liquidation_type->LookupName;
              break;
            case 8://8 "Sucursal"
              $addressEvaluation = $subsidary->CustomFields->c->address;
              $placeEvaluation   = $declaration->liquidation_type->LookupName;
              break;
            case 9://9 "Taller"
              $addressEvaluation = $subsidary->CustomFields->c->address;
              $placeEvaluation   = $declaration->liquidation_type->LookupName;
              break;
            case 10://10  "Terceros"
              $addressEvaluation = "";
              $placeEvaluation   = "Sucursal";
              break;
          } */

          $contactDenouncer    = $incident->PrimaryContact; //Tercero

          $pre_desc_incidente  = $declaration->declaration_cause->LookupName." - ".$declaration->declaration_consequence->LookupName." ".$declaration->declaration_damages;
          $desc_incidente      = preg_replace("/[\r\n|\n|\r]+/", " ", $pre_desc_incidente);
          $desc_incidente_2    = preg_replace("/[\r\n|\n|\r]+/", " ", $declaration->declaration_description);

          $a_request["Evaluacion"] = array(
                                            "poliza"                      => (int) $declaration->policy_number,  // Modificación: Se agregó un cast para setear el tipo de dato a numérico.
                                            "id_siniestro"                => $incident->CustomFields->c->claim_number, //número de iaxis
                                            // "taller"                   => ($subsidary->Name === null)?"": $subsidary->Name,
                                            // "taller_rut"               => ($subsidary->CustomFields->c->rut === null)?"": $subsidary->CustomFields->c->rut,
                                            // "taller_sucursal"          => ($subsidary->CustomFields->c->subsidiarynumber === null)?"": $subsidary->CustomFields->c->subsidiarynumber,
                                            // "direccion_evaluacion"     => ($addressEvaluation === null)?"": $addressEvaluation,
                                            // "id_region"                => (string) $subsidary->CustomFields->REALE->Comuna->prov_id->reg_id->reg_id,
                                            // "lugar_evaluacion"            => $placeEvaluation, // Modificación: Se agregó condición para añadirlo al arreglo.
                                            "fecha_creacion"              => substr(date('d-m-Y',$declaration->declaration_incidentDate), 0, 10), // Modificación: Se cortó la longitud máxima de la cadena al límite indicado en el documento de integraciones de Boreal.
                                            "rut_liquidador"              => (int) $liquidator['rut'], // Modificación: Se agregó un cast para setear el tipo de dato a numérico.
                                            "nombre_liquidador"           => substr(trim($liquidator['name']), 0, 150), // Modificación: Se cortó la longitud máxima de la cadena al límite indicado en el documento de integraciones de Boreal.
                                            "tipo_evaluacion"             => 'Principal',
                                            "tipo_incidente"              => 'Indemnizar',
                                            // "desc_incidente"              => urlencode($desc_incidente),
                                            "desc_incidente"              => substr(rawurlencode($desc_incidente), 0, 3000), // Modificación: Se cambió 'urlencode' por 'rawurlencode' y se cortó la longitud máxima de la cadena al límite inndicado en el documento de integraciones de Boreal.
                                            // "desc_incidente_2"            => urlencode($desc_incidente_2), // No Obligatorio.
                                            "poliza_item"                 => (int) $declaration->policy_item, // Modificación: Se agregó un cast para setear el tipo de dato a numérico.
                                            "cobertura"                   => "RC Daño Emergente", // Modificación: Se cortó la longitud máxima de la cadena al límite indicado en el documento de integraciones de Boreal.
                                            "cod_cobertura"               => 1313, // Modificación: Se agregó un cast para setear el tipo de dato a numérico.
                                            //"id_marca"                    => $incident->CustomFields->c->vehicle_brand_code,
                                            // "nro_chasis"                  => ($declaration->risk_chassis === null)?"": $declaration->risk_chassis, // No Obligatorio Boreal. Modificación.
                                            //"marca"                       => substr(trim($incident->CustomFields->REALE->DriverPerson->Brand), 0, 150), // Modificación: Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
                                            //"modelo"                      => substr(trim($incident->CustomFields->REALE->DriverPerson->Model), 0, 150), // Modificación: Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
                                            //"ano"                         => ($incident->CustomFields->REALE->DriverPerson->Year) ? $incident->CustomFields->REALE->DriverPerson->Year : 0,
                                            // "placa"                       => substr(trim($declaration->risk_plate), 0, 6), // Modificación: Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
                                            // "serial_motor"                => substr($declaration->risk_serial_motor, 0, 20), // Modificación: Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
                                            // "vehiculo_color"           => '', // No Obligatorio
                                            // "vehiculo_km"              => '', // No Obligatorio
                                            "nro_tramitacion"             => $incident->CustomFields->c->process_number,
                                            "condicion_persona"           => 'Tercero',
                                            "rut"                         => $contactDenouncer->CustomFields->c->rut,
                                            "nombre_persona"              => substr(rawurlencode($contactDenouncer->Name->First." ".$contactDenouncer->Name->Last), 0, 150), // Modificación: Se agregó la codificación de URLENCODE y se cortó la longitud máxima de la cadena al límite inndicado en el documento de integraciones de Boreal.
                                            "fecha_planificacion"         => substr(date('d-m-Y'), 0, 10), // Modificación: Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
                                            "hora"                        => (int) date('h'), // Modificación: Se agregó un cast para setear el tipo de dato a numérico.
                                            "minutos"                     => (int) date('i'), // Modificación: Se agregó un cast para setear el tipo de dato a numérico.
                                            "meridiem"                    => date('A'),
                                            "id_crm"                      => $incident->ReferenceNumber,
                                            "fecha_creacion_ticket_crm"   => substr(date('d-m-Y',$incident->CreatedTime), 0, 10), // Modificación: Se agregó un cast para setear el tipo de dato a numérico y se cortó la longitud máxima de la cadena al límite inndicado en el documento de integraciones de Boreal.
                                            "fecha_asignacion_ticket_crm" => substr(date('d-m-Y'), 0, 10), // Modificación: Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
                                            "deducible"                   => 0,
                                            "tipo_producto"               => $declaration->plan->Code
                                           );
                                        

          //Marca de Vehículo
          if ($incident->CustomFields->c->vehicle_brand != null)
          {
            $brand                            = $incident->CustomFields->c->vehicle_brand;
            $a_request["Evaluacion"]["marca"] = substr(trim($brand), 0, 150);
            $vehicleBrand                     = RNCPHP\REALE\VehicleBrand::first("Name like '%{$brand}%'");
            if($vehicleBrand instanceof RNCPHP\REALE\VehicleBrand)
            {
              $a_request["Evaluacion"]["id_marca"]  =  $vehicleBrand->CMARCA; //ID de Marca en caso de que la encontró
            }
            else
            {
              $a_request["Evaluacion"]["id_marca"]  =  null;
            }
          }
          else
          {
            $a_request["Evaluacion"]["marca"]     =  substr(trim($incident->CustomFields->DriverPerson->Brand), 0, 150);
            $a_request["Evaluacion"]["id_marca"]  =  null;
          }

          // Modelo de vehículo
          if ($incident->CustomFields->c->vehicle_model != null)
          {
            $a_request["Evaluacion"]["modelo"] =  substr(trim($incident->CustomFields->c->vehicle_model), 0, 150);
          }
          else
          {
            $a_request["Evaluacion"]["modelo"] =  substr(trim($incident->CustomFields->REALE->DriverPerson->Model), 0, 150);
          }

          // Año de vehículo
          if ($incident->CustomFields->c->fabrication_year > 0)
          {
            $a_request["Evaluacion"]["ano"]    = $incident->CustomFields->c->fabrication_year;
          }
          else
          {
            $a_request["Evaluacion"]["ano"]    = ($incident->CustomFields->REALE->DriverPerson->Year) ? $incident->CustomFields->REALE->DriverPerson->Year : 0;
          }

          if(strlen(substr($incident->CustomFields->c->vehicle_motor, 0, 20)) === 20)
          {
            $a_request["Evaluacion"]["serial_motor"] = substr($incident->CustomFields->c->vehicle_motor, 0, 20); // Modificación: Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
          }

          if(strlen(substr(trim($incident->CustomFields->c->vehicle_plate), 0, 6)) === 6)
          {
            $a_request["Evaluacion"]["placa"] = substr(trim($incident->CustomFields->c->vehicle_plate), 0, 6); // Modificación: Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
          }

          // Descripción Incidente 2
          if(!empty(trim($desc_incidente_2)))
          {
            $a_request["Evaluacion"]['desc_incidente_2'] =  substr(rawurlencode($desc_incidente_2), 0, 1500); // Modificación: Si hay una descripción incidente2 se agrega al arreglo bajo la codificación 'rawurlencode' y se cortó la longitud máxima de la cadena al límite inndicado en el documento de integraciones de Boreal.
          }

          // Lugar de Evaluación
          if(!empty($placeEvaluation))
          {
            $a_request["Evaluacion"]['lugar_evaluacion'] = substr($placeEvaluation, 0, 13); // Modificación: Si existe lugar de evaluación se agrega en el arreglo y se cortó la longitud máxima de la cadena al límite inndicado en el documento de integraciones de Boreal.
          }

          // Número de Chasis
          if(!empty( $incident->CustomFields->c->vehicle_serial))
          {
            $a_request["Evaluacion"]['nro_chasis'] = substr($incident->CustomFields->c->vehicle_serial, 0, 17); // Modificación: Se evalua si se posee el número de chasis, de tenerlo se agrega en el arreglo, de lo contrario omitirlo.
            // Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
          }

          // Dirección evaluación
          if(!empty($addressEvaluation))
          {
            $a_request["Evaluacion"]['direccion_evaluacion'] = substr(rawurlencode($addressEvaluation), 0, 150); // Modificación: Se agregó la codificación de URLENCODE y se cortó la longitud máxima de la cadena al límite inndicado en el documento de integraciones de Boreal.
          }

          // Taller
          if(!empty($subsidary->Name))
          {
            $a_request["Evaluacion"]['taller'] = substr($subsidary->Name, 0, 150); // Modificación: Se cortó la longitud de la cadena a una que pueda ser aceptada por Boreal.
          }
          // Taller Rut
          if($subsidary->CustomFields->c->rut != null)
          {
            $a_request["Evaluacion"]['taller_rut'] = $subsidary->CustomFields->c->rut;
          }

          // Taller Sucursal
          if(!empty($subsidary->CustomFields->c->subsidiarynumber))
          {
            $a_request["Evaluacion"]['taller_sucursal'] = $subsidary->CustomFields->c->subsidiarynumber;
          }

          // ID de Región
          if(!empty($subsidary->CustomFields->REALE->Comuna->prov_id->reg_id->reg_id))
          {
            // $a_request["Evaluacion"]['id_region'] = (string) $subsidary->CustomFields->REALE->Comuna->prov_id->reg_id->reg_id;
            $a_request["Evaluacion"]['id_region'] = $subsidary->CustomFields->REALE->Comuna->prov_id->reg_id->reg_id; // Modificación: 'id_región' debe ser numérico, según documento Boreal.
          }

          // Estos 2 campos se añaden a solicitud de Boreal 
          // Cobertura Taller
          if (!empty($declaration->policy_cover)) 
          {
            $a_request["Evaluacion"]['cobertura_taller'] = $declaration->policy_cover->LookupName;
          }

          // Suma Asegurada
          if (!empty($declaration->MontoAsegClp)) 
          {
            $a_request["Evaluacion"]['suma_asegurada'] = $declaration->MontoAsegClp;
          }


          $jsonDataEncoded = json_encode($a_request);

          self::insertPrivateNote($incident, "RC_CreateDamageEvaluation. Json Enviado: ".$jsonDataEncoded);

          $result = ConnectUrl::requestCURLJsonRaw(self::URL, $jsonDataEncoded);
          self::insertPrivateNote($incident, "RC_CreateDamageEvaluation. Respuesta del servicio: " . $result);

          if ($result === false)
          {
            self::insertPrivateNote($incident, "RC_CreateDamageEvaluation. Error interno de CRM: ".ConnectUrl::getResponseError()." ".$result);
            $incident->CustomFields->c->invoke_damage_evaluation = null ;
          }
          else
          {
            $a_response = json_decode($result, true);
            if (is_array($a_response))
            {
              if ($a_response["codigo"] > 0)
              {
                self::insertPrivateNote($incident, "RC_CreateDamageEvaluation. Integración realizada con {$a_response['message']}, código de boreal {$a_response['codigo']}");
                $incident->CustomFields->c->boreal_number          = $a_response["codigo"];
                $incident->CustomFields->c->last_liquidator_assign = $liquidator['ID'];

                if ($incident->CustomFields->REALE->Liquidador)
                {
                  if (empty($incident->AssignedTo))
                  {
                    $incident->AssignedTo  = new RNCPHP\GroupAccount();
                  }
                  $incident->AssignedTo->Account = $incident->CustomFields->REALE->Liquidador->ID;
                }
                else
                {
                  $incident->AssignedTo->Account = RNCPHP\Account::fetch(12399); //Abigail
                }
              }
              else
              {
                self::insertPrivateNote($incident, "RC_CreateDamageEvaluation. Error de respuesta Boreal: " . $result);
                $incident->CustomFields->c->invoke_damage_evaluation = null;

              }
            }
            else
            {
              self::insertPrivateNote($incident, "RC_CreateDamageEvaluation. Error de respuesta Boreal, Json no valido: " . $result);
              $incident->CustomFields->c->invoke_damage_evaluation = null;
            }

          }

          // $incident->Save(RNCPHP\RNObject::SuppressAll);
          // return;

          //Se pregunta si el tipo de liquidacion es remota.
          if ($incident->CustomFields->REALE->DeclarationDetail->liquidation_type->ID === 6)
          {
            $incident->CustomFields->c->is_remote = true;
          }

          $incident->Save();
          return;

        }
        else
        {
          self::insertPrivateNote($incident, "RC_CreateDamageEvaluation. El requerimiento debe ser un ticket de RC.");
        }

      }
      catch (RNCPHP\ConnectAPIError $err)
      {
          self::insertPrivateNote($incident, "RC_CreateDamageEvaluation. " . $err->getMessage() . ", línea " . $err->getLine());
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
