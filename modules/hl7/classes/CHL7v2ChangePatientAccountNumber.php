<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;
use Exception;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Class CHL7v2ChangePatientIdentifierList
 * Change patient account number, message XML HL7
 */
class CHL7v2ChangePatientAccountNumber extends CHL7v2MessageXML {
  static $event_codes = array("A49");

  /**
   * Get contents
   *
   * @return array
   * @throws Exception
   */
  function getContentNodes() {
    $data = parent::getContentNodes();

    $PID = $data["PID"];
    // AN - Patient Account Number (NDA)
    foreach ($this->query("PID.18", $PID) as $_node) {
      if ($this->queryTextNode("CX.5", $_node) == "PREAD") {
        $data["admitIdentifiers"]["NPA"] = $this->queryTextNode("CX.1", $_node);
      }
    }

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
   * @throws Exception
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

    // Acquittement d'erreur : identifiant NPA non fourni
    if (!$data['admitIdentifiers']) {
      return $exchange_hl7v2->setAckAR($ack, "E200", null, $patient);
    }

    $function_handle = "handle$exchange_hl7v2->code";

    if (!method_exists($this, $function_handle)) {
      return $exchange_hl7v2->setAckAR($ack, "E006", null, $patient);
    }

    return $this->$function_handle($ack, $patient, $data);
  }

  /**
   * Handle event A49
   *
   * @param CHL7Acknowledgment $ack     Acknowledgment
   * @param CPatient           $patient Person
   * @param array              $data    Data
   *
   * @return string
   * @throws Exception
   */
  function handleA49(CHL7Acknowledgment $ack, CPatient $patient, $data) {
    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender         = $exchange_hl7v2->_ref_sender;

    $PI = CMbArray::get($data["personIdentifiers"], "PI");
    if (!$PI) {
      return $exchange_hl7v2->setAckAR($ack, "E900", null, $patient);
    }

    $patient->_IPP = $PI;
    $patient->loadFromIPP($sender->group_id);
    if (!$patient->_id) {
      return $exchange_hl7v2->setAckAR($ack, "E901", null, $patient);
    }

    // @todo actuellement utilisé pour gérer la récupération d'un numéro de pré-admission
    $MRG_3 = $this->queryNodes("MRG.3", $data["MRG"])->item(0);

    // Il s'agit de l'identifiant interne Mediboard
    if ($this->queryTextNode("CX.5", $MRG_3) == "ANT") {
      $ID_MB = $this->queryTextNode("CX.1", $MRG_3);
    }

    $sejour = new CSejour();
    $sejour->load($ID_MB);
    if (!$sejour->_id) {
      return $exchange_hl7v2->setAckAR($ack, "E902", null, $sejour);
    }

    if ($sejour->patient_id != $patient->_id) {
      return $exchange_hl7v2->setAckAR($ack, "E903", null, $sejour);
    }

    $NPA = CMbArray::get($data["admitIdentifiers"], "NPA");
    if (!$NPA) {
      return $exchange_hl7v2->setAckAR($ack, "E904", null, $sejour);
    }

    // Si le NPA existe déjà
    $NPA = CIdSante400::getMatch("CSejour", CSejour::getTagNDA($sender->group_id), $NPA);
    if ($NPA->_id) {
      return $exchange_hl7v2->setAckAR($ack, "E905", null, $sejour);
    }

    $NPA->object_id = $sejour->_id;
    if ($msg = $NPA->store()) {
      return $exchange_hl7v2->setAckAR($ack, "E906", $msg, $sejour);
    }

    return $exchange_hl7v2->setAckAA($ack, "I901", null, $sejour);
  }
}