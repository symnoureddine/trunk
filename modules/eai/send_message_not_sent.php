<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CValue;
use Ox\Interop\Hl7\CExchangeHL7v2;
use Ox\Interop\Hl7\CHL7v2Error;
use Ox\Interop\Hl7\CHL7v2Message;
use Ox\Interop\Hl7\CHL7v2MessageXML;
use Ox\Interop\Hl7\CReceiverHL7v2;
use Ox\Interop\Hl7\Events\CHL7v2Event;
use Ox\Interop\Ihe\CIHE;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\System\CExchangeSource;

CCanDo::checkRead();

$receiver_guid = CValue::get("receiver_guid");
$exclude_event = CValue::get("exclude_event");
$include_event = CValue::get("include_event");

/** @var CReceiverHL7v2 $receiver */
$receiver = CMbObject::loadFromGuid($receiver_guid);

if (!$receiver && !$receiver->_id && !$receiver->actif) {
  return;
}
$receiver->loadConfigValues();

$where = '';

$echange_hl7v2 = new CExchangeHL7v2();
$ds = $echange_hl7v2->getDS();
$where['statut_acquittement']     = "IS NULL";
$where['sender_id']               = "IS NULL";
$where['receiver_id']             = "= '$receiver->_id'";
$where['message_valide']          = "= '1'";
$where['send_datetime']            = "IS NULL";
$where['date_production']         = "BETWEEN '".CMbDT::dateTime("-3 DAYS")."' AND '".CMbDT::dateTime("+1 DAYS")."'";
if ($exclude_event) {
  $exclude_event = explode("|", $exclude_event);
  $where['code'] = $ds->prepareNotIn($exclude_event);
}
if ($include_event) {
  $include_event = explode("|", $include_event);
  $where['code'] = $ds->prepareIn($include_event);
}

/** @var CExchangeHL7v2[] $exchanges */
$exchanges = $echange_hl7v2->loadList($where, "date_production DESC");

// Effectue le traitement d'enregistrement des notifications sur lequel le cron vient de passer
// ce qui permet la gestion des doublons
foreach ($exchanges as $_exchange) {
  $_exchange->send_datetime = CMbDT::dateTime();
  $_exchange->store();
}

$receiver->synchronous = "1";

foreach ($exchanges as $_exchange) {
  try {
    $_exchange->_ref_receiver = $receiver;
    $object = CMbObject::loadFromGuid("$_exchange->object_class-$_exchange->object_id");
    if (!$object) {
      $_exchange->send_datetime = "";
      $_exchange->store();
      continue;
    }

    //Récupération du séjour et du patient en fonction de l'objet
    switch ($_exchange->object_class) {
      case "CPatient":
        /** @var CPatient $patient */
        $patient = $object;
        //Recherche du séjour en cours
        $sejours = $patient->getCurrSejour(null, $receiver->group_id);
        $sejour = reset($sejours);
        //Récupération du dernier séjour
        if (!$sejour) {
          $sejours = $patient->loadRefsSejours();
          $sejour = reset($sejours);
        }
        break;
      case "CSejour":
        /** @var CSejour $sejour */
        $sejour = $object;
        $patient = $sejour->loadRefPatient();
        break;
      default:
        $_exchange->send_datetime = "";
        $_exchange->store();
        continue 2;
    }

    $patient->loadIPP();
    if (!$patient->_IPP || $patient->_IPP === "waiting") {
      $_exchange->send_datetime = "";
      $_exchange->store();
      continue;
    }

    if ($_exchange->sous_type == "ITI30" && $_exchange->code != "A08") {
      $present_sejour = true;
      $present_patient = $patient && !$patient->_id;
    }
    else {
      $present_patient = $patient && !$patient->_id;
      $present_sejour = $sejour && !$sejour->_id;

      $sejour->loadNDA();
      if (!$sejour->_NDA || $sejour->_NDA === "waiting") {
        $_exchange->send_datetime = "";
        $_exchange->store();
        continue;
      }
    }

    //S'il n'y a pas de séjour ou de patient en focntion de la transaction, on passe au prochaine échange
    if ($present_sejour && $present_patient) {
      $_exchange->send_datetime = "";
      $_exchange->store();
      continue;
    }

    $object->_receiver = $receiver;

    /** @var CHL7v2Event $data_format */
    $data_format = CIHE::getEvent($_exchange);
    $data_format->handle($_exchange->_message);
    $data_format->_exchange_hl7v2 = $_exchange;
    $data_format->_receiver = $receiver;
    /** @var CHL7v2MessageXML $xml */
    $xml = $data_format->message->toXML();

    $PID = $xml->queryNode("PID");
    $ipp = $xml->queryNode("PID.3", $PID);

    $PV1 = $xml->queryNode("PV1");
    $nda = $xml->queryNode("PV1.19", $PV1);

    if ((!$ipp || $ipp && $ipp->nodeValue == "waiting") || (!$nda || $nda && $nda->nodeValue == "waiting")) {
      CHL7v2Message::setBuildMode($receiver->_configs["build_mode"]);
      $data_format->build($object);
      CHL7v2Message::resetBuildMode();

      $data_format->flatten();
      if (!$data_format->message->isOK(CHL7v2Error::E_ERROR)) {
        $_exchange->send_datetime = "";
        $_exchange->store();
        continue;
      }
    }

    if ($_exchange->code != "A40" &&
        (((!$ipp && !$ipp->nodeValue) || $ipp->nodeValue == "0") ||
        (($_exchange->sous_type != "ITI30" ||
        ($_exchange->sous_type == "ITI30" && $_exchange->code == "A08")) && !$nda && empty($nda->nodeValue)))
    ) {

      CHL7v2Message::setBuildMode($receiver->_configs["build_mode"]);
      $data_format->build($object);
      CHL7v2Message::resetBuildMode();

      $data_format->flatten();
      if (!$data_format->message->isOK(CHL7v2Error::E_ERROR)) {
        $_exchange->send_datetime = "";
        $_exchange->store();
        continue;
      }
    }

    $evt    = $receiver->getEventMessage($data_format->profil);
    $source = CExchangeSource::get("$receiver->_guid-$evt");

    if (!$source->_id || !$source->active) {
      new CMbException("Source inactive");
    }

    $msg = $data_format->msg_hl7 ? $data_format->msg_hl7 : $_exchange->_message;
    if ($receiver->_configs["encoding"] == "UTF-8") {
      $msg = utf8_encode($msg);
    }

    $_exchange->send_datetime = CMbDT::dateTime();
    $source->setData($msg, null, $_exchange);
    try {
      $source->send();
    }
    catch (CMbException $e) {
      $_exchange->send_datetime = "";
      $_exchange->store();
      //Si un problème survient lors de l'envoie, on arrête le script pour ne aps rompre la séquentialité
       $e->stepAjax(UI_MSG_ERROR);
    }
    $_exchange->response_datetime = CMbDT::dateTime();
    $_exchange->_acquittement     = $source->getACQ();
    $_exchange->store();
  }
  catch (Exception $e) {
    $_exchange->send_datetime = "";
    $_exchange->store();
    continue;
  }
}