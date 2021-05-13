<?php 
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Interop\Eai\CExchangeDataFormat;
use Ox\Interop\Eai\CExchangeTabular;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\Eai\CInteropSender;
use Ox\Interop\Hl7\CHL7v2Message;
use Ox\Interop\Hl7\CHL7v2MessageXML;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\CContentTabular;

$exchange_guid = CView::get("exchange_guid", "str");
CView::checkin();

// Chargement de l'échange demandé
/** @var CExchangeDataFormat $exchange */
$exchange = CMbObject::loadFromGuid($exchange_guid);

$exchange->loadRefs();
$exchange->loadRefsInteropActor();
$exchange->getErrors();
$exchange->getObservations();

$limit_size = 100;

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("exchange", $exchange);

switch (true) {
  case $exchange instanceof CExchangeTabular:
    CMbObject::$useObjectCache = false;

    $msg_segment_group = $exchange->getMessage();

    if ($msg_segment_group) {
      $doc = $msg_segment_group->toXML();
      if (count($msg_segment_group->children) > $limit_size) {
        $doc->formatOutput       = true;
        $msg_segment_group->_xml = "<pre>" . CMbString::htmlEntities($doc->saveXML()) . "</pre>";
      }
      else {
        $msg_segment_group->_xml = $doc->saveXML();
      }
    }

    $ack_segment_group = $exchange->getACK();

    if ($ack_segment_group) {
      $doc = $ack_segment_group->toXML();
      if (count($ack_segment_group->children) > $limit_size) {
        $doc->formatOutput       = true;
        $ack_segment_group->_xml = "<pre>" . CMbString::htmlEntities($doc->saveXML()) . "</pre>";
      }
      else {
        $ack_segment_group->_xml = $doc->saveXML();
      }
    }

    $exchange->loadRefsInteropActor();
    if ($exchange->receiver_id) {
      /** @var CInteropReceiver $actor */
      $actor = $exchange->_ref_receiver;
      $actor->loadConfigValues();
    }
    else {
      /** @var CInteropSender $actor */
      $actor = $exchange->_ref_sender;
      $actor->getConfigs($exchange);
    }

    $content_tabular = new CContentTabular();
    $content_tabular->load($exchange->message_content_id);

    $hl7_message = new CHL7v2Message;
    $hl7_message->parse($content_tabular->content);

    /** @var CHL7v2MessageXML $xml */
    $xml = $hl7_message->toXML(null, false);

    // Récupération de l'IPP dans le message si présent
    $control_identifier_type_code = CValue::read($sender->_configs, "control_identifier_type_code");
    if (CHL7v2Message::$handle_mode === "simple" || !$control_identifier_type_code) {
      $IPP_message = CHL7v2Message::getIdentifier($xml, "//PID.3", "CX.1");
    }
    else {
      $IPP_message = CHL7v2Message::getIdentifier($xml, "//PID.3", "CX.1", "CX.5", "PI");
    }

    // Récupération du NDA dans le message si présent
    if (CMbArray::get($actor->_configs, "handle_NDA") == "PV1_19") {
      if (CHL7v2Message::$handle_mode === "simple" || !$control_identifier_type_code) {
        $NDA_message = CHL7v2Message::getIdentifier($xml, "//PV1.19", "CX.1");
      }
      else {
        $NDA_message = CHL7v2Message::getIdentifier($xml, "//PV1.19", "CX.1", "CX.5", "AN");
      }
    }
    else {
      if (CHL7v2Message::$handle_mode === "simple" || !$control_identifier_type_code) {
        $NDA_message = CHL7v2Message::getIdentifier($xml, "//PID.18", "CX.1");
      }
      else {
        $NDA_message = CHL7v2Message::getIdentifier($xml, "//PID.18", "CX.1", "CX.5", "AN");
      }
    }

    $patient_found = new CPatient();
    $admit_found   = new CSejour();
    $admits_found  = array();
    $patients_found  = array();

    $date_observation = null;
    $date_observation = CHL7v2Message::getDateObservation($xml, "//ORU_R01.OBSERVATION[1]/OBX", "OBX.14");
    if (!$date_observation) {
      $date_observation = CHL7v2Message::getDateObservation($xml, "//ORU_R01.ORDER_OBSERVATION[1]/OBR", "OBR.7");
    }
    if ($date_observation) {
      $date_observation = CMbDT::dateTime($date_observation);
    }

    // On recherche le patient par Nom/Prénom/Date de naissance
    if (!$NDA_message && !$IPP_message) {
      $patients_found = CHL7v2Message::getPatients($xml, $actor->group_id);
    }

    // On recherche le NDA à partir de l'IPP
    if (!$NDA_message && $IPP_message) {

      if ($date_observation) {
        CMbDT::dateTime($date_observation);
      }

      $admits_found = CHL7v2Message::getAdmits($IPP_message, $actor, $date_observation);
    }

    // On recherche l'IPP à partir du NDA
    if ($NDA_message && !$IPP_message) {
      $NDA = CIdSante400::getMatch("CSejour", $actor->_tag_sejour, $NDA_message);

      if ($NDA->_id) {
        /** @var CSejour $sejour */
        $sejour = CMbObject::loadFromGuid("$NDA->object_class-$NDA->object_id");
        $patient_found = $sejour->loadRefPatient();
        $patient_found->loadIPP($actor->group_id);
      }
    }

    if ($IPP_message) {
      $idex_patient = CIdSante400::getMatch("CPatient", $actor->_tag_patient, $IPP_message);

      /** @var CPatient $patient_found */
      $patient_found = CMbObject::loadFromGuid("$idex_patient->object_class-$idex_patient->object_id");
      $patient_found->loadIPP($actor->group_id);
    }

    if ($NDA_message) {
      $idex_sejour = CIdSante400::getMatch("CSejour", $actor->_tag_sejour, $NDA_message);

      /** @var CSejour $admit_found */
      $admit_found = CMbObject::loadFromGuid("$idex_sejour->object_class-$idex_sejour->object_id");
      $admit_found->loadNDA($actor->group_id);
      $admit_found->loadRefPatient()->loadIPP($actor->group_id);
    }

    $info_patient_message = CHL7v2Message::getInfoPatient($xml);

    $date_entree_sejour = CHL7v2Message::getDateObservation($xml, "PV1", "PV1.44/TS.1");
    if ($date_entree_sejour) {
      $date_entree_sejour = CMbDT::dateTime($date_entree_sejour);
    }

    $date_sortie_sejour = CHL7v2Message::getDateObservation($xml, "PV1", "PV1.45/TS.1");
    if ($date_sortie_sejour) {
      $date_sortie_sejour = CMbDT::dateTime($date_sortie_sejour);
    }

    $smarty->assign("ipp_message"         , $IPP_message);
    $smarty->assign("nda_message"         , $NDA_message);
    $smarty->assign("patient_found"       , $patient_found);
    $smarty->assign("patients_found"      , $patients_found);
    $smarty->assign("admits_found"        , $admits_found);
    $smarty->assign("admit_found"         , $admit_found);
    $smarty->assign("date_observation"    , $date_observation);
    $smarty->assign("info_patient_message", $info_patient_message);
    $smarty->assign("date_sortie_sejour"  , $date_sortie_sejour);
    $smarty->assign("date_entree_sejour"  , $date_entree_sejour);
    $smarty->assign("actor"               , $actor);
    $smarty->assign("exchange"            , $exchange);
    $smarty->display("inc_edit_message_hl7.tpl");
    break;
}