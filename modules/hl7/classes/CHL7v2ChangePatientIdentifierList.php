<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;
use Ox\AppFine\Server\CAppFineServer;
use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Class CHL7v2ChangePatientIdentifierList 
 * Change patient identifier list, message XML HL7
 */
class CHL7v2ChangePatientIdentifierList extends CHL7v2MessageXML {
  static $event_codes = array ("A46", "A47");

  /**
   * Get contents
   *
   * @return array
   */
  function getContentNodes() {
    $data = parent::getContentNodes();

    $this->queryNode("MRG", null, $data, true);
       
    return $data;
  }

  /**
   * Handle change patient identifier list message
   *
   * @param CHL7Acknowledgment $ack     Acknowledgment
   * @param CMbObject          $patient Person
   * @param array              $data    Data
   *
   * @return string
   */
  function handle(CHL7Acknowledgment $ack = null, CMbObject $patient = null, $data = array()) {
    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender       = $exchange_hl7v2->_ref_sender;
    $sender->loadConfigValues();

    $this->_ref_sender = $sender;

    // Acquittement d'erreur : identifiants RI et PI non fournis
    if (!$data['personIdentifiers']) {
      return $exchange_hl7v2->setAckAR($ack, "E100", null, $patient);
    }
 
    $function_handle = "handle$exchange_hl7v2->code";
    
    if (!method_exists($this, $function_handle)) {
      return $exchange_hl7v2->setAckAR($ack, "E006", null, $patient);
    }
    
    return $this->$function_handle($ack, $patient, $data);
  }

  /**
   * Handle event A46
   *
   * @param CHL7Acknowledgment $ack     Acknowledgment
   * @param CPatient           $patient Person
   * @param array              $data    Data
   *
   * @return string
   */
  function handleA46(CHL7Acknowledgment $ack, CPatient $patient, $data) {
    $handle_mode = CHL7v2Message::$handle_mode;
    
    CHL7v2Message::$handle_mode = "simple";
    
    $msg = $this->handleA47($ack, $patient, $data);
    
    CHL7v2Message::$handle_mode = $handle_mode;
    
    return $msg;
  }

  /**
   * Handle event A47
   *
   * @param CHL7Acknowledgment $ack     Acknowledgment
   * @param CPatient           $patient Person
   * @param array              $data    Data
   *
   * @return string
   */
  function handleA47(CHL7Acknowledgment $ack, CPatient $patient, $data) {
    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender       = $exchange_hl7v2->_ref_sender;
    $sender->loadConfigValues();
   
    $this->_ref_sender = $sender;
    
    $incorrect_identifier = null;

    if (CModule::getActive('appFine')) {
      if ($sender->_configs['handle_portail_patient']) {
        return CAppFineServer::handleA47($ack, $data, $sender, $exchange_hl7v2);
      }
    }

    // Traitement du mode simple, cad
    if (CHL7v2Message::$handle_mode == "simple") {
      $MRG_4 = $this->queryNodes("MRG.4", $data["MRG"])->item(0);
      
      $incorrect_identifier = $this->queryTextNode("CX.1", $MRG_4);

      $patient->load($incorrect_identifier);

      // ID non connu (non fourni ou non retrouvé)
      if (!$incorrect_identifier || !$patient->_id) {
        return $exchange_hl7v2->setAckAR($ack, "E141", null, $patient);
      }
    }
    else {
      $MRG_1 = $this->queryNodes("MRG.1", $data["MRG"])->item(0);

      if ($this->queryTextNode("CX.5", $MRG_1) == "PI") {
        $incorrect_identifier = $this->queryTextNode("CX.1", $MRG_1);

        // Chargement de l'IPP
        $IPP_incorrect = new CIdSante400();
        if ($incorrect_identifier) {
          $IPP_incorrect = CIdSante400::getMatch("CPatient", $sender->_tag_patient, $incorrect_identifier);
        }

        // PI non connu (non fourni ou non retrouvé)
        if (!$incorrect_identifier || !$IPP_incorrect->_id) {
          return $exchange_hl7v2->setAckAR($ack, "E141", null, $patient);
        }

        $patient->load($IPP_incorrect->object_id);

        // Passage en trash de l'IPP du patient a éliminer
        if ($msg = $patient->trashIPP($IPP_incorrect)) {
          return $exchange_hl7v2->setAckAR($ack, "E140", $msg, $patient);
        }
      }

      if ($this->queryTextNode("CX.5", $MRG_1) == "RI") {
        // Notre propre RI
        $guid = "CGroups-$sender->group_id";
        if ($this->queryTextNode("CX.4/HD.1", $MRG_1) == CAppUI::conf("hl7 CHL7 assigning_authority_namespace_id" , $guid)
            || $this->queryTextNode("CX.4/HD.2", $MRG_1) == CAppUI::conf("hl7 CHL7 assigning_authority_universal_id", $guid)
        ) {
          $patient_id = $this->queryTextNode("CX.1", $MRG_1);
          $patient->load($patient_id);
        }
      }
    }

    // Sauvegarde du nouvel IPP
    $IPP = new CIdSante400();
    $IPP->object_id    = $patient->_id;
    $IPP->object_class = "CPatient";
    $IPP->id400        = $data['personIdentifiers']["PI"];
    $IPP->tag          = $sender->_tag_patient;

    if ($msg = $IPP->store()) {
      return $exchange_hl7v2->setAckAR($ack, "E140", $msg, $patient);
    }  
    
    return $exchange_hl7v2->setAckAA($ack, "I140", null, $patient);
  }
}
