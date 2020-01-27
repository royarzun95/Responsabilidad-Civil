<?php
/*
 * Given a CPHP object, appends a string to the specified field and saves the
 * object.
 */
namespace Custom\Libraries\CPM\v1;

use RightNow\Connect\v1_3 as RNCPHP;

class ConnectUrl
{
    public static $msgError = 'error';
    public static $user = NULL;
    public static $passwd = NULL;

    function __construct()
    {
    }

    static function requestPost($url, $postArray, $typeRequest ='CURL')
    {
        switch ($typeRequest) {
            case 'CURL':
                return self::requestCURLByPost($url, $postArray);
                break;
            case 'FileGetContent':
                return self::requestFileGetContentByPost($url, $postArray);
                break;
            default:
                return self::requestCURLByPost($url, $postArray);
                break;
        }
    }

    static function requestFileGetContentByPost($url, $postArray)
    {

        $headers = @get_headers($url);
        $statusCode = substr($headers[0], 9, 3);
        if($statusCode != '200'){
            self::$msgError = 'No se pudo resolver la petición a la URL, codigo de Error: '. $statusCode;
            return false;
        }

        if (is_array($postArray))
            $postString =  http_build_query($postArray);

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' =>  $postString
            )
        );

        $context  = stream_context_create($opts);
        $result   = file_get_contents($url, false, $context);



        return $result;
    }

    static function requestCURLByPost($url, $postArray, $postfields = NULL, $is_boreal = FALSE)
    {
        if($postArray !== NULL)
        {
            if (is_array($postArray))
                $postString = http_build_query($postArray, '', '&');
        }

        
        
        load_curl();
        $ch = curl_init($url);
        
        # Setting our options
        
        if($is_boreal === TRUE)
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        }
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_POST, 1);

        if($postArray !== NULL)
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        else
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
        //curl_setopt($ch, 156, 500);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        # Get the response
        $response = curl_exec($ch);

        if(curl_errno($ch))
        {
            $info = curl_getinfo($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            self::$msgError = curl_error($ch) . ". Status Code ". $http_code . ". URL: " . $info['url'];
            //self::$msgError .='<br>Tiempo ' . $info['total_time'] . ' segundos en recibir la respuesta de la siguiente URL: ' . $info['url'];
            curl_close($ch);
            return false;
        }

        if ($response != false)
        {
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($statusCode != '200')
            {
                self::$msgError = "No se pudo resolver la petición a la URL {$url}, codigo de Error: ". $statusCode;
                return false;
            }
            else
                return $response;
        }
        else
        {
            curl_close($ch);
            self::$msgError = 'No se pudo resolver la petición a la URL';
            return false;
        }
    }

    static function requestCURLJsonRaw($url, $jsonDataEncoded, $timeout = null)
    {
      load_curl();
      $ch                     = curl_init($url);

      //Tell cURL that we want to send a POST request.
      curl_setopt($ch, CURLOPT_POST, 1);

      //Attach our encoded JSON string to the POST fields.
      curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);

      // Coookie Development
      curl_setopt($ch, CURLOPT_COOKIE, 'location=development%7EZlVjakRxVmZBVGltT1FIZE9QdTJkRUE4SjJ6QmVTSDdXR3J1bnFOT1cxUjhRfn5tZkEwX2Q0RTdla0s0dEpiUm9HclpMWGR6N1BGZXFPMUY2NE0yWlRjeXNSempOcmRMc2MwMzd3TnJmTzFRaXBRTGxxODJ5RWF_elJlNWhkMGJZWTFNT0w3eU1mZGRIbDFLM2NkSTRhaWc5OUxGZkZCbGR1');
                                        
      //Set the content type to application/json
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      if($timeout)
      {
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
      }
      else
      {
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
      }

      //Execute the request
      $data                   = curl_exec($ch);

      if (curl_errno($ch))
      {
          $info               = curl_getinfo($ch);
          self::$msgError     = curl_errno( $ch )." ".curl_error($ch);
          curl_close($ch);
          return false;
      }

      if ($data              != false)
      {
          $statusCode         = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);
          if ($statusCode    != '200')
          {
              self::$msgError = "No se pudo resolver la petición a la URL {$url}, código de Error: ". $statusCode;
              return false;
          }
          else
              return $data;
      }
      else
      {
          curl_close($ch);
          self::$msgError     = "No se pudo resolver la petición a la URL ".$url;
          return false;
      }
    }

    static function requestGet($url, $user = null, $passwd = null)
    {
      load_curl();
      $ch = curl_init();

      if(!empty($user) && !empty($passwd))
      {
         curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
         curl_setopt($ch, CURLOPT_USERPWD, "{$user}:{$passwd}");
      }

      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_PROXY, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);



      $data = curl_exec($ch);


      if (curl_errno($ch))
      {

          $info               = curl_getinfo($ch);
          self::$msgError     = curl_errno( $ch )." ".curl_error($ch);
          curl_close($ch);
          return false;
      }

      if ($data != false)
      {
          $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);
          if ($statusCode != 200)
          {
            if($statusCode === 401)
                self::$msgError = "Error en la autenticación, favor verifique usuario y contraseña.";
            else
                self::$msgError = 'No se pudo resolver la petición a la URL, codigo de Error: '. $statusCode;
            return false;
          }
          else
          {
            return $data;
          }
      }
      else
      {
          curl_close($ch);
          self::$msgError = 'No se pudo resolver la petición a la URL';
          return false;
      }

    }

    static function getResponseError()
    {
        return self::$msgError;
    }

    static function getItems($url)
    {
        if(!empty(self::$user) && !empty(self::$passwd))
            $result_files = self::requestGet($url, self::$user, self::$passwd);
        else
            $result_files = self::requestGet($url);

        $arr_result_files = json_decode($result_files, true);
        if(!is_array($arr_result_files))
        {
            $arr_result_files = false;
        }
        return $arr_result_files;
    }

    static function getDataFile($url)
    {
        $url = $url. "/data";
        if(!empty(self::$user) && !empty(self::$passwd))
            $response = self::requestGet($url, self::$user, self::$passwd);
        else
            $response = self::requestGet($url);

        return $response;
    }

    static function setAuthData($user, $passwd)
    {
        self::$user     = $user;
        self::$passwd   = $passwd;
        return TRUE;
    }

    /**
     * Método que permite el envío de un formulario con archivos adjuntos por POST
     *
     * @author          Javier Castro <jcastro@integer.cl>
     * @param string    $url.              URL del endpoint al cuál se quiere realizar la petición.
     * @param array     $a_data            Arreglo asociativo del formulario que se enviará.
     * @param array     $a_files_data     Arreglo simple que contiene la ruta absoluta (ruta del servidor) de los archivos que se quieren enviar.
     * @return string|bool.  Retornará una cadena de texto en caso de éxito y falso en caso de error.
     */
    static function requestCURLByPostWithFiles($url, $a_data, $a_files_data, $is_docuware = TRUE)
    {
        load_curl();
        $ch     = curl_init($url);
        if($is_docuware === TRUE)
        {
            $a_file = array();
    
            $temp                   = tmpfile();
            $path                   = stream_get_meta_data($temp)['uri']; // eg: /tmp/phpFx0513a
            file_put_contents($path, base64_decode($a_files_data["data"]));
    
            $cfile                  = curl_file_create($path, $a_files_data["contentType"], $a_files_data["fileName"]);
            $a_file["documento"]    = $cfile;
            $a_post = array_merge($a_data, $a_file);
        }
        else
        {
            $a_data["img"] = $a_files_data["data"];
            $a_post = $a_data;
        }

        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $a_post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
        # Get the response
        $response = curl_exec($ch);

        fclose($temp); // esto elimina el archivo temporal


        if (curl_errno($ch))
        {
          $info = curl_getinfo($ch);
          self::$msgError = curl_error($ch);
          self::$msgError .='Tiempo ' . $info['total_time'] . ' segundos en recibir la respuesta de la siguiente URL: ' . $info['url'];
          curl_close($ch);
          return false;
        }


        if ($response !== false)
        {
          $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

          curl_close($ch);
          if ($statusCode !== 200)
          {
           // $response  = utf8_encode($response);
            $a_response = json_decode($response, true);
            if(is_array($a_response))
            {
              if(array_key_exists("message", $a_response))
              {
                self::$msgError = 'No se pudo resolver la petición a la URL, codigo de Error: '. $statusCode.'. Motivo:'.$a_response["message"];
              }
              else
              {
                self::$msgError = 'No se pudo resolver la petición a la URL, codigo de Error: '. $statusCode.'<br>';
              }
            }
            else
            {
              self::$msgError = 'No se pudo resolver la petición a la URL, codigo de Error: '. $statusCode.'<br>';
            }
            return false;
          }
          else
          {
            return $response;
          }
        }
        else
        {
          self::$msgError = curl_error($ch);
          curl_close($ch);
          return false;
        }
    }

    static function requestPostException($url)
    {
        load_curl();
        $curl = curl_init();

        curl_setopt_array($curl, 
            array(
                CURLOPT_PORT => "8081",
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    "Accept: */*",
                    "Accept-Encoding: gzip, deflate",
                    "Cache-Control: no-cache",
                    "Connection: keep-alive",
                    "Content-Length: 0",
                    "cache-control: no-cache"
                ),
            )
        );

        $response = curl_exec($curl);
        $err      = curl_error($curl);

        curl_close($curl);

        if ($err) 
        {
            self::$msgError = "cURL Error #:" . $err;
            return FALSE;
        } 
        else 
        {
            return $response;
        }
    }

}
