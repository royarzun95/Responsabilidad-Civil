<?php
/**
 * CPMObjectEventHandler: RCAssignRandomLiquidator
 * Package: RN
 * Objects: Incident
 * Actions: Update
 * Version: 1.3
 * Purpose: Minimal CPM handler for Incident update.
 */
use \RightNow\Connect\v1_3 as RNCPHP;
use \RightNow\CPM\v1 as RNCPM;
/**
 * Handler class for CPM.
 */
class RCAssignRandomLiquidator implements RNCPM\ObjectEventHandler {
    /**
     * Apply CPM logic to object.
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
        try
        {
          $incident = RNCPHP\Incident::fetch($incident->ID);
          if(!empty($incident->CustomFields->REALE->Liquidador) || isset($incident->CustomFields->REALE->Liquidador))
          {
            $incident->AssignedTo->Account = $incident->CustomFields->REALE->Liquidador;
            $incident->save(RNCPHP\RNObject::SuppressAll);
          }
          else
          {
            $liq_obj                                   = RNCPHP\Account::fetch(103);
            if(!empty($liq_obj))
            {
                $incident->CustomFields->REALE->Liquidador = $liq_obj;
                $incident->AssignedTo->Account             = $incident->CustomFields->REALE->Liquidador;
                self::insertPrivateNote($incident,"Se ha asignado al liquidador ".$incident->CustomFields->REALE->Liquidador->Name->First." ".$incident->CustomFields->REALE->Liquidador->Name->Last);
            }
            else
            {
                self::insertPrivateNote($incident,"No pudo ser asignado al liquidador ".$incident->CustomFields->REALE->Liquidador->Name->First." ".$incident->CustomFields->REALE->Liquidador->Name->Last);
            }
          }
          $incident->save(RNCPHP\RNObject::SuppressAll);
        }
        catch (RNCPHP\ConnectAPIError $err) 
        {
        self::insertPrivateNote($incident, $err->getMessage());
        }
    }
     static function insertPrivateNote($incident, $textoNP)
     {
         try
         {
             $incident->Threads = new RNCPHP\ThreadArray();
             $incident->Threads[0] = new RNCPHP\Thread();
             $incident->Threads[0]->EntryType = new RNCPHP\NamedIDOptList();
             $incident->Threads[0]->EntryType->ID = 1; // 1: nota privada
             $incident->Threads[0]->Text = $textoNP;
             $incident->Save(RNCPHP\RNObject::SuppressAll);
         }
         catch ( RNCPHP\ConnectAPIError $err )
         {
             return false;
         }
     }
 }
class RCAssignRandomLiquidator_TestHarness implements RNCPM\ObjectEventHandler_TestHarness
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
?>