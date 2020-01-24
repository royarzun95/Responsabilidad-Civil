<?php

namespace Custom\Libraries\CPM\v1;
use RightNow\Connect\v1_3 as RNCPHP;

require 'ConnectUrl.php';

class UploadImagesToBoreal
{
  CONST URL_GET_IMAGE_NAME   = "http://boldo.realechile.cl/ws_obtener_nombre_imagen.asp"; // URL Boreal Test
  CONST URL_UPLOAD_IMAGE     = "http://boldo.realechile.cl/php/file-echo-canvas.php";
  CONST URL_REST_API         = "https://reale--tst2.custhelp.com/services/rest/connect/v1.3/incidents";
  CONST URL_CC_UPLOAD_FILES  = "http://reale--tst2.custhelp.com/cc/CivilLiability/uploadFilesToBoreal"; // Test

  public static function execute($runMode, $action, $incident, $cycle)
  {
    try
    {
      // $a_extensions      = array("jpg","jpeg", "jpe", "jif", "jfif", "jfi", "png", "bmp", "gif", "webp", "tiff", "tif", "psd", "raw", "arw", "cr2", "nrw", "k25", "dib", "svg");
      // $a_imagesBoreal    = array();
      // $q_fileAttachments = count($incident->FileAttachments);

      // if($q_fileAttachments > 0)
      // {
      //   for($i = 0; $i < $q_fileAttachments; $i++)
      //   {
      //     $a_fileName = explode('.', $incident->FileAttachments[$i]->FileName);
      //     $sure_ext = $a_fileName[1];
      //     // $file_extension = $a_fileName[count($a_fileName) - 1];

      //     if(in_array($sure_ext, $a_extensions))
      //     {
      //       $a_imagesBoreal[$incident->FileAttachments[$i]->ID] = $incident->FileAttachments[$i];
      //     }
      //     self::insertPrivateNote($incident, "'UploadImagesToBoreal': Arreglo ". print_r($a_imagesBoreal, TRUE));
      //   }
      // }

      // // self::insertPrivateNote($incident, "'UploadImagesToBoreal': Arreglo ". print_r($a_imagesBoreal, TRUE));
      
      // $q_borealImages = count($a_imagesBoreal);
      // if($q_borealImages < 1)
      // {
      //   self::insertPrivateNote($incident, "'UploadImagesToBoreal': El requerimiento no tiene imágenes para subir a Boreal.");
      //   return;
      // }


      // self::insertPrivateNote($incident, "'UploadImagesToBoreal': Se han encontrado {$q_borealImages} imágenes que serán subidas a Boreal.");

      // $boreal_number = $incident->CustomFields->c->boreal_number;
      // if(!$boreal_number)
      // {
      //   self::insertPrivateNote($incident, "'UploadImagesToBoreal': El requerimiento no tiene número de evaluación de daños Boreal.");
      //   return;
      // }

      // // Se setea la autenticación básica para el uso de Rest API
      // ConnectUrl::setAuthData("Integer1", "|r90E5a[Le?["); // Utilizar las credenciales de mandato en ambiente de producción

      // foreach($a_imagesBoreal as $image)
      // {
      //   /**
      //    * Se consumirá el servicio que obtiene el nombre del archivo
      //   */
      //   $a_request_fileName = array(
      //     "incidente2" => $boreal_number
      //   );
      //   $resp_fileName = ConnectUrl::requestCURLByPost(self::URL_GET_IMAGE_NAME, $a_request_fileName, TRUE);
        
      //   /**
      //    * {
      //    *   "contactos": [{
      //    *    "id": "193960",
      //    *     "prox_estado": "Evaluacion abierta",
      //    *    "propietario": "4359",
      //    *     "codigo": "193960"
      //    *  }]
      //    *  }
      //    */
      //   if($resp_fileName === FALSE)
      //   {
      //     self::insertPrivateNote($incident, "'UploadImagesToBoreal': ". ConnectUrl::getResponseError());
      //     return;
      //   }
  
      //   $a_fileName = json_decode($resp_fileName, TRUE);
      //   $a_contacts = $a_fileName["contactos"];
        
      //   foreach($a_contacts as $contact)
      //   {
      //     self::upload_image($incident, $contact["id"], array($image));
      //   }

      // }


      // Logica Nueva
      //   {
      //     "id_crm": "191224-000003"
      //  }
      $a_incident = array("id_crm" => $incident->ReferenceNumber);
      $json_incident = json_encode($a_incident);
      self::insertPrivateNote($incident, "Inicio Lógica subida de imagenes Boreal");
      ConnectUrl::requestCURLJsonRaw(self::URL_CC_UPLOAD_FILES, $json_incident, 4);

    }
    catch (RNCPHP\ConnectAPIError $err) 
    {
      self::insertPrivateNote($incident, "CPM UploadImagesToBoreal: " . $err->getMessage());
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
      $url_target = self::URL_REST_API . "/" . $incident->ID . "/fileAttachments";

      foreach($a_imagesBoreal as $image)
      {
        $url_image                 = $url_target . "/" . $image->ID;
        $data_response             = json_decode(ConnectUrl::getDataFile($url_image), TRUE);
        $a_fileData["data"]        = $data_response["data"];

        $a_fileData["contentType"] = $image->ContentType;
        $a_fileData["fileName"]    = date('Ymdhis',time()). "_" . $image->FileName;

        $a_data = array(
          "nombre" => $id_image,
          "img" => NULL
        );


        $response = ConnectUrl::requestCURLByPostWithFiles(self::URL_UPLOAD_IMAGE, $a_data, $a_fileData, FALSE);
        if($response === FALSE)
        {
          self::insertPrivateNote($incident, "'UploadImagesToBoreal': Error desde Curl al intentar subir la imagen {$image->FileName} a Boreal. ".ConnectUrl::getResponseError());
          continue;
        }

        self::insertPrivateNote($incident, "'UploadImagesToBoreal': Imagen {$image->FileName} subida a Boreal exitosamente. Respuesta de Boreal: " . $response);
      }
      return TRUE;
    } 
    catch (\Exception $e) 
    {
      self::insertPrivateNote($incident, "CPM UploadImagesToBoreal: " . $e->getMessage());
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
