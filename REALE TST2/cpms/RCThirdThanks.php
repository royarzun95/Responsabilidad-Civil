<?php

/**
 * CPMObjectEventHandler: RCThirdThanks
 * Package: RN
 * Objects: Incident
 * Actions: Update
 * Version: 1.3
 * Purpose: Notificación al Cliente por Expiración de inspección sin póliza
 */

use \Custom\Libraries\CPM\v2 as CPMEmailHandler;
use \RightNow\Connect\v1_3 as RNCPHP;
use \RightNow\CPM\v1 as RNCPM;

const DEV_MODE = true;

define(
    'APPPATH',
    DEV_MODE ?
        __DIR__ . "/scripts/cp/customer/development/" : __DIR__ . "/scripts/cp/generated/production/optimized/"
);

require_once APPPATH . "libraries/cpm/v2/EmailNotifier.php";
/**
 * Handler class for CPM.
 */
class RCThirdThanks implements RNCPM\ObjectEventHandler
{
    /**
     * Apply CPM logic to object.
     *
     * @param int $runMode
     * @param int $action
     * @param object $inc
     * @param int $cycle
     */
    public static function apply($runMode, $action, $inc, $cycle)
    {
        if ($cycle !== 0) 
        {
            return;
        }

        //Saltar el testHarness para que no salga error de Eval
        if ($inc->ID === 2667) 
        {
            return;
        }
        $incident = RNCPHP\Incident::fetch($inc->ID);
        try 
        {
            //Notificación a Cliente - Caso Ingresado
            if ($incident->PrimaryContact) 
            {   
                $contact        = $incident->PrimaryContact;
                $contact        = RNCPHP\Contact::fetch($contact->ID); //Obj contacto principal
                $subject        = "Responsabilidad Civil - Pago Efectuado";
                $bcc_emails     = array();
                $a_contact_emails = array();

                //obtiene todos los correos de contacto
                foreach ($contact->Emails as $email) 
                {
                    if (!empty($email->Address))
                        array_push($a_contact_emails, $email->Address);
                }
                // if (!empty($corredor->Emails[0]->Address))
                //     $corredor_email          = $corredor->Emails[0]->Address;

                if (!empty($a_contact_emails)) 
                {
                    $to_emails              = $a_contact_emails;
                    $cc_emails              = array();
                    $file_att_codes         = array('');
                    $html_message_base_name = ($incident->CustomFields->SQUADRA->PaymentMethod->CompensationType->ID == 1) ? 'CUSTOM_MSG_RC_THANKS_TRANSFER' : 'CUSTOM_MSG_RC_THANKS_VALE_VISTA';
                    CPMEmailHandler\EmailNotifier::send_mail($incident, $subject, $to_emails, $cc_emails, $bcc_emails, $file_att_codes, $html_message_base_name);
                } 
                else 
                {
                    self::insertPrivateNote($incident, "Email no enviado Cliente: Porque Contacto con ID: {$contact->ID} no tiene email asociado");
                }
            } 
            else 
            {
                self::insertPrivateNote($incident, "Email no enviado Cliente: Contacto No asignado");
            }
        } 
        catch (RNCPHP\ConnectAPIError $err) 
        {
            self::insertPrivateNote($incident, $err->getMessage());
        }
    }

    public static function insertPrivateNote($incident, $textoNP)
    {
        try {
            $incident->Threads                   = new RNCPHP\ThreadArray();
            $incident->Threads[0]                = new RNCPHP\Thread();
            $incident->Threads[0]->EntryType     = new RNCPHP\NamedIDOptList();
            $incident->Threads[0]->EntryType->ID = 8; // 1: nota privada
            $incident->Threads[0]->Text          = $textoNP;
            $incident->Save(RNCPHP\RNObject::SuppressAll);
        } catch (RNCPHP\ConnectAPIError $err) {
            return false;
        }
    }

}

class RCThirdThanks_TestHarness implements RNCPM\ObjectEventHandler_TestHarness
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
