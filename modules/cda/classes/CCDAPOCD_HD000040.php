<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;

use DOMNode;
use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CValue;
use Ox\Interop\Eai\CInteropSender;
use Ox\Mediboard\Patients\CAntecedent;
use Ox\Mediboard\Patients\CAntecedentSnomed;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Class CCDAPOCD_HD000040
 * CDA InfrastructureRoot
 */
class CCDAPOCD_HD000040 extends CCDADomDocument {
  static $mapping_type_antecedent = array(
    "2.25.299518904337880959076241620201932965147.17.10.4" => "alle",
    "2.25.299518904337880959076241620201932965147.17.10.1" => "med",
    "2.25.299518904337880959076241620201932965147.17.10.3" => "chir",
    "2.25.299518904337880959076241620201932965147.17.10.2" => "gyn",
    "2.25.299518904337880959076241620201932965147.17.10.5" => "fam",
  );

  /**
   * @inheritdoc
   */
  function getContentNodes() {
    $data = parent::getContentNodes();

    $data["components"] = $this->queryNodes(
      "component/cda:structuredBody/cda:component/cda:section/cda:component",
      null,
      $varnull,
      true
    );

    return $data;
  }

  /**
   * @inheritdoc
   */
  function handle(CMbObject $object, $data = array()) {
    /** @var CPatient $patient */
    $patient = $object;
    $exchange_cda = $this->_ref_exchange_cda;
    $sender = $this->_ref_sender = $exchange_cda->_ref_sender;

    // Patient
    $patientPI = CValue::read($data['personIdentifiers'], "PI");
    if (!$patientPI) {
      CAppUI::stepAjax("CExchangeCDA-msg-Patient not found by IPP", UI_MSG_WARNING);
      return;
    }

    $IPP = CIdSante400::getMatch("CPatient", $sender->_tag_patient, $patientPI);
    // Patient non retrouvé par son IPP
    if (!$IPP->_id) {
      CAppUI::stepAjax("CExchangeCDA-msg-Patient not found by IPP", UI_MSG_WARNING);
      return;
    }
    $patient->load($IPP->object_id);

    // Récupération ou création du dossier médical
    if (!$dossier_id = CDossierMedical::dossierMedicalId($patient->_id, $patient->_class)) {
      CAppUI::stepAjax("CExchangeCDA-msg-Medical folder not found", UI_MSG_WARNING);
      return;
    }

    // Récupération des components
    foreach ($data["components"] as $_component_node) {
      foreach ($this->queryNodes("section/cda:entry", $_component_node) as $_entry) {
        $this->mappingAntecedent($_entry, $dossier_id, $sender);
      }
    }
  }

  /**
   * Mapping antecedent
   *
   * @param DOMNode        $_entry     DOM entry
   * @param int            $dossier_id Dossier ID
   * @param CInteropSender $sender     Sender
   *
   * @return void
   * @throws Exception
   */
  function mappingAntecedent(DOMNode $_entry, $dossier_id, $sender) {
    $antecedent = new CAntecedent();
    $antecedent->dossier_medical_id = $dossier_id;
    $rques = $this->queryTextNode("observation/cda:text", $_entry, ".");

    if (!$rques) {
      return;
    }

    $idex = new CIdSante400();
    // On essaye de retrouver l'antécédent par son identifiant
    $value_id = $this->getValueAttributNode($this->queryNode("observation/cda:id", $_entry), "root");

    if ($value_id) {
      $idex = CIdSante400::getMatch($antecedent->_class, $sender->_tag_hl7, $value_id , $antecedent->_id);
      if ($idex->_id) {
        $antecedent->load($idex->object_id);
      }
    }

    $antecedent->rques = $rques;
    $antecedent->escapeValues();

    // Si on a pas l'identifiant on fait un loadMatching sur le nom
    if (!$antecedent->_id) {
      $antecedent->loadMatchingObject();
    }

    $antecedent->rques = $rques;
    // Type of antecedent
    $type_antecedent = $this->getType($_entry);
    if ($type_antecedent) {
      $antecedent->type  = $type_antecedent;
    }

    // Certainly
    $degree_certainty = $this->getCertainly($_entry);
    if ($degree_certainty) {
      // Si degrés de certitude vaut "inexact", on doit annuler l'antécédent
      if ($degree_certainty == "inexact") {
        $antecedent->annule = 1;
      }
      else {
        $antecedent->degree_certainty = $degree_certainty;
        $antecedent->annule           = 0;
      }
    }

    // Start date
    $start_date = $this->getStartDate($_entry);
    if ($start_date) {
      $antecedent->date = $start_date;
    }

    // End date
    $end_date = $this->getEndDate($_entry);
    if ($end_date) {
      $antecedent->date_fin = $end_date;
    }

    if ($msg = $antecedent->store()) {
      CAppUI::stepAjax($msg, UI_MSG_WARNING);
      return;
    }

    // Store de l'idex
    if ($value_id) {
      $idex->setObject($antecedent);
      $idex->store();
    }

    CAppUI::stepAjax("CAntecedent-msg-create");

    $this->getCodeSNOMED($_entry, $antecedent);
  }



  /**
   * Get type of antecedent from XML
   *
   * @param DOMNode  $_entry entry node
   *
   * @return String
   */
  function getType(DOMNode $_entry) {
    return CMbArray::get(
      self::$mapping_type_antecedent, $this->queryTextNode("observation/cda:templateId/@root", $_entry)
    );
  }

  /**
   * Get certainly from XML
   *
   * @param DOMNode $_entry entry node
   *
   * @return String|null
   * @throws Exception
   */
  function getCertainly(DOMNode $_entry) {
    $node_code_certitude = $this->queryNode(
      "observation/cda:entryRelationship/cda:observation/cda:code[@code='17.10.0.1']", $_entry
    );

    if (!$node_code_certitude) {
      return null;
    }

    return $this->getValueAttributNode($this->getValue($node_code_certitude->parentNode), "code");
  }

  /**
   * Get start date from XML
   *
   * @param DOMNode $_entry entry node
   *
   * @return String|null
   * @throws Exception
   */
  function getStartDate(DOMNode $_entry) {
    $node_code_start_date = $this->queryNode(
      "observation/cda:entryRelationship/cda:observation/cda:code[@code='17.10.1.2']", $_entry
    );

    if (!$node_code_start_date) {
      return null;
    }

    $start_date = $this->getValueAttributNode($this->getValue($node_code_start_date->parentNode), "value");
    switch (strlen($start_date)) {
      case 4:
        $format_start_date = $start_date."-00-00";
        break;
      case 6:
        $values_date = str_split($start_date, 2);
        $format_start_date = CMbArray::get($values_date, 0).CMbArray::get($values_date, 1)."-".CMbArray::get($values_date, 2)."-00";
        break;
      case 8:
        $format_start_date  = CMbDT::date($start_date);
        break;
      default:
        return null;
    }

    return $format_start_date;
  }

  /**
   * Get end date from XML
   *
   * @param DOMNode $_entry entry node
   *
   * @return String|null
   * @throws Exception
   */
  function getEndDate(DOMNode $_entry) {
    $node_code_end_date = $this->queryNode(
      "observation/cda:entryRelationship/cda:observation/cda:code[@code='17.10.1.3']", $_entry
    );

    if (!$node_code_end_date) {
      return null;
    }

    $end_date = $this->getValueAttributNode($this->getValue($node_code_end_date->parentNode), "value");
    switch (strlen($end_date)) {
      case 4:
        $format_start_date = $end_date."-00-00";
        break;
      case 6:
        $values_date = str_split($end_date, 2);
        $format_start_date = CMbArray::get($values_date, 0).CMbArray::get($values_date, 1)."-".CMbArray::get($values_date, 2)."-00";
        break;
      case 8:
        $format_start_date  = CMbDT::date($end_date);
        break;
      default:
        return null;
    }

    return $format_start_date;
  }

  /**
   * Get code snomed form XML and store it
   *
   * @param DOMNode     $_entry     entry node
   * @param CAntecedent $antecedent antecedent
   *
   * @return void
   * @throws Exception
   */
  function getCodeSNOMED(DOMNode $_entry, CAntecedent $antecedent) {
    switch ($antecedent->type) {
      case "alle":
        $node_code_snomed = $this->queryNode(
          "observation/cda:entryRelationship/cda:observation/cda:code[@code='17.10.4.1']", $_entry
        );
        $code_snomed = $this->getValueAttributNode($this->getValue($node_code_snomed->parentNode), "code");
        break;
      default:
        $code_snomed = $this->getValueAttributNode($this->queryNode("observation/cda:code", $_entry), "code");
    }

    if (!$code_snomed) {
      return null;
    }

    $antecedent_snomed = new CAntecedentSnomed();
    $antecedent_snomed->code = $code_snomed;
    $antecedent_snomed->antecedent_id = $antecedent->_id;
    $antecedent_snomed->loadMatchingObject();
    if ($msg = $antecedent_snomed->store()) {
      CAppUI::stepAjax($msg, UI_MSG_WARNING);
    }
    else {
      CAppUI::stepAjax("CAntecedentSnomed-msg-create");
    }
  }
}