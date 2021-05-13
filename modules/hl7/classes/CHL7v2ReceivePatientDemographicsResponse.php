<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;
use DOMNode;
use Ox\Core\CMbObject;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Class CHL7v2ReceivePatientDemographicsResponse
 * Receive patient demographics response, message XML HL7
 */
class CHL7v2ReceivePatientDemographicsResponse extends CHL7v2MessageXML {

  /** @var string */
  static $event_codes = array ("K22", "ZV2");

  /**
   * Get data nodes
   *
   * @return array Get nodes
   */
  function getContentNodes() {
    $data  = array();

    $this->queryNode("QAK", null, $data, true);

    $this->queryNode("QPD", null, $data, true);

    $query_response = $this->queryNodes("RSP_K22.QUERY_RESPONSE|RSP_ZV2.QUERY_RESPONSE", null, $varnull, true);
    foreach ($query_response as $_query_response) {
      // Patient
      $this->queryNodes("PID", $_query_response, $data, true);

      // Admit
      $this->queryNodes("PV1", $_query_response, $data, true);
      $this->queryNodes("PV2", $_query_response, $data, true);
    }

    $this->queryNode("DSC", null, $data, true);

    return $data;
  }

  /**
   * @inheritdoc
   */
  function handle(CHL7Acknowledgment $ack = null, CMbObject $newPatient = null, $data = array()) {
    $data = $this->getContentNodes();

    $response_status = $this->getQueryResponseStatus($data["QAK"]);

    // Aucun résultat ou en erreur
    if ($response_status != "OK" || !array_key_exists("PID", $data)) {
      return array();
    }

    $objects = array();

    $i = 1;

    $recordPerson = new CHL7v2RecordPerson();

    if (!empty($data["PV1"])) {
      foreach ($data["PV1"] as $key => $_PV1) {
        $patient = new CPatient();
        $sejour  = new CSejour();

        $this->getAdmit($data, $key, $sejour, $recordPerson, $patient);

        $objects[$i] = $sejour;

        $i++;
      }
    }
    else {
      foreach ($data["PID"] as $_PID) {
        $patient = new CPatient();
        $this->getPerson($_PID, $patient, $recordPerson);

        $objects[$i] = $patient;

        $i++;
      }
    }

    if ($DSC = $data["DSC"]) {
      $objects["pointer"] = $this->getContinuationPointer($DSC);
    }

    if ($QPD = $data["QPD"]) {
      $objects["query_tag"] = $this->getQueryTag($QPD);
    }

    return $objects;
  }

  /**
   * Get PV1
   *
   * @param array              $data         data
   * @param int                $key          Key
   * @param CSejour            $sejour       Admit
   * @param CHL7v2RecordPerson $recordPerson Record person
   * @param CPatient           $patient      Person
   *
   * @return void
   */
  function getAdmit($data, $key, CSejour $sejour, CHL7v2RecordPerson $recordPerson, CPatient $patient) {
    $PID = $data["PID"][$key];
    $PV1 = $data["PV1"][$key];
    $PV2 = null;
    if (!empty($data["PV2"])) {
      $PV2 = $data["PV2"][$key];
    }

    $this->getPerson($PID, $patient, $recordPerson);

    $sejour->_ref_patient = $patient;

    $recordAdmit = new CHL7v2RecordAdmit();
    $recordAdmit->getPatientClass($PV1, $sejour);

    $sejour->entree_reelle = $this->queryTextNode("PV1.44", $PV1);
    $sejour->sortie_reelle = $this->queryTextNode("PV1.45", $PV1);

    if ($PV2) {
      $sejour->entree_prevue = $this->queryTextNode("PV2.8", $PV2);
      $sejour->sortie_prevue = $this->queryTextNode("PV2.9", $PV2);
    }

    $sejour->_NDA = "";
    $sejour->_OID = "";
    foreach ($this->queryNodes("PID.18", $PID) as $_PID_18) {
      $sejour->_NDA .= $this->queryTextNode("CX.1"     , $_PID_18) . "\n";
      $sejour->_OID .= $this->queryTextNode("CX.4/HD.2", $_PID_18) . "\n";
    }

    $sejour->updateFormFields();
    $sejour->updatePlainFields();
    $sejour->loadRefsNotes();
  }

  /**
   * Get PID
   *
   * @param DOMNode            $node         Node
   * @param CPatient           $patient      Person
   * @param CHL7v2RecordPerson $recordPerson Record person
   *
   * @return void
   */
  function getPerson(DOMNode $node, CPatient $patient, CHL7v2RecordPerson $recordPerson) {
    $recordPerson->getPID($node, $patient);

    $patient->_IPP = "";
    $patient->_OID = "";
    foreach ($this->queryNodes("PID.3", $node) as $_PID_3) {
      $patient->_IPP .= $this->queryTextNode("CX.1", $_PID_3) . "\n";
      $patient->_OID .= $this->queryTextNode("CX.4/HD.2", $_PID_3) . "\n";
    }

    $patient->updateFormFields();
    $patient->loadRefsNotes();
  }

    /**
   * Get query response status
   *
   * @param DOMNode $node QAK element
   *
   * @return string
   */
  function getQueryResponseStatus(DOMNode $node) {
    return $this->queryTextNode("QAK.2", $node);
  }

  /**
   * Get continuation pointer
   *
   * @param DOMNode $node DSC element
   *
   * @return string
   */
  function getContinuationPointer(DOMNode $node) {
    return $this->queryTextNode("DSC.1", $node);
  }

  /**
   * Get query tag
   *
   * @param DOMNode $node QPD element
   *
   * @return string
   */
  function getQueryTag(DOMNode $node) {
    return $this->queryTextNode("QPD.2", $node);
  }
}
