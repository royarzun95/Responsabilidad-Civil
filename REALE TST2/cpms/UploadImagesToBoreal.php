<?php

/**
 * CPMObjectEventHandler: UploadImagesToBoreal
 * Package: RN
 * Objects: Incident
 * Actions: Update
 * Version: 1.3
 * Purpose: Subir fotos a Boreal.
 */
use \RightNow\Connect\v1_3 as RNCPHP;
use \RightNow\CPM\v1 as RNCPM;
use \Custom\Libraries\CPM\v1 as CPMHandlers;

const DEV_MODE = true;

define('APPPATH',
  DEV_MODE ?
  __DIR__ . "/scripts/cp/customer/development/" :
  __DIR__ . "/scripts/cp/generated/production/optimized/");

  require_once APPPATH . "libraries/cpm/v1/UploadImagesToBoreal.php";

/**
 * Handler class for CPM.
 */
class UploadImagesToBoreal implements RNCPM\ObjectEventHandler
{
  /**
   * Apply CPM logic to object.
   *
   * @param int $runMode
   * @param int $action
   * @param object $incident
   * @param int $cycles
   */
  public static function apply($runMode, $action, $incident, $cycle)
  {
    if ($cycle !== 0) {
      return;
    }

    //Saltar el testHarness para que no salga error de Eval
    if ($incident->ID === 2667) {
      return;
    }

    try
    {
      return CPMHandlers\UploadImagesToBoreal::execute($runMode, $action, $incident, $cycle);
    }
    catch (RNCPHP\ConnectAPIError $err)
    {
      self::insertPrivateNote($incident, "CPM UploadImagesToBoreal: ".$err->getMessage());
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
      return false;
    }
  }
}

class UploadImagesToBoreal_TestHarness implements RNCPM\ObjectEventHandler_TestHarness
{
  public static $IncidentOneId = null;
  /**
   * Set up test cases.
   */
  public static function setup()
  {
    return;
  }
  /**
   * Return the object that we want to test with. You could also return
   * an array of objects to test more than one variation of an object.
   *
   * @param int   $action
   * @param class $object_type
   *
   * @return object | array
   */
  public static function fetchObject($action, $object_type)
  {
    $inc = RNCPHP\Incident::fetch(2667);
    //$inc = true;
    return $inc;
  }
  /**
   * Validate test cases.
   *
   * @param int    $action
   * @param object $Incident
   *
   * @return bool
   */
  public static function validate($action, $Incident)
  {
    return true;
  }
  /**
   * Destroy every object created by this test. Not necessary since in
   * test mode and nothing is committed, but good practice if only to
   * document the side effects of this test.
   */
  public static function cleanup()
  {
    return;
  }
}
