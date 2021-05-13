<?php
/**
 * @package Mediboard\Hprimxml
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CValue;
use Ox\Interop\Hprimxml\CDestinataireHprim;
use Ox\Interop\Hprimxml\CEchangeHprim;
use Ox\Interop\Hprimxml\CHPrimXMLAcquittementsPatients;
use Ox\Mediboard\System\CExchangeSource;

/**
 * Send message
 */
CCanDo::checkRead();

$echange_hprim_id         = CValue::get("echange_xml_id");
$echange_hprim_classname  = CValue::get("echange_xml_classname");

$where = '';
if (!$echange_hprim_id) {
  if (!($limit = CAppUI::conf('sip batch_count'))) {
    return;
  }
  $echange_hprim = new CEchangeHprim();
  $where['statut_acquittement']     = "IS NULL";
  $where['sender_id']               = "IS NULL";
  $where['receiver_id']             = "IS NOT NULL";
  $where['message_valide']          = "= '1'";
  $where['acquittement_valide']     = "IS NULL"; 
  $where['acquittement_content_id'] = "IS NULL"; 
  $where['send_datetime']            = "IS NULL";
  $where['date_production']         = "BETWEEN '".CMbDT::dateTime("-3 DAYS")."' AND '".CMbDT::dateTime("+1 DAYS")."'";
  
  $notifications = $echange_hprim->loadList($where, null, $limit);

  // Effectue le traitement d'enregistrement des notifications sur lequel le cron vient de passer
  // ce qui permet la gestion des doublons
  foreach ($notifications as $notification) {
    /** @var CEchangeHprim $notification */
    $notification->send_datetime = CMbDT::dateTime();
    $notification->store();
  }
  
  foreach ($notifications as $notification) {      
    $dest_hprim = new CDestinataireHprim();
    $dest_hprim->load($notification->receiver_id);
        
    if ($dest_hprim->actif) {
      $source = CExchangeSource::get("$dest_hprim->_guid-evenementPatient");
      $source->setData($notification->_message);
      try {
        $source->send();
      }
      catch(Exception $e) {
        $notification->send_datetime = "";
        $notification->store();
        continue;
      }
      
      $acquittement = $source->getACQ();
      
      if ($acquittement) {
        $domGetAcquittement = new CHPrimXMLAcquittementsPatients();
        $domGetAcquittement->loadXML($acquittement);
        $doc_valid = $domGetAcquittement->schemaValidate(null, false, $dest_hprim->display_errors);
        if ($doc_valid) {
          $notification->statut_acquittement = $domGetAcquittement->getStatutAcquittementPatient();
        }
        $notification->acquittement_valide = $doc_valid ? 1 : 0;
        
        $notification->send_datetime = CMbDT::dateTime();
        $notification->_acquittement = $acquittement;
        $notification->store();
      }
      else {
        $notification->send_datetime = "";
        $notification->store();
      }   
    }
    else {
      $notification->send_datetime = "";
      $notification->store();
    }
  }
}
else {
  // Chargement de l'objet
  /** @var CEchangeHprim $echange_hprim */
  $echange_hprim = new $echange_hprim_classname;
  $echange_hprim->load($echange_hprim_id);
  
  $dest_hprim = new CDestinataireHprim();
  $dest_hprim->load($echange_hprim->receiver_id);
  
  $source = CExchangeSource::get("$dest_hprim->_guid-evenementPatient");
  $source->setData($echange_hprim->_message);
  $source->send();
  $acquittement = $source->getACQ();
  
  if ($acquittement) {
    $domGetAcquittement = new CHPrimXMLAcquittementsPatients();
    $domGetAcquittement->loadXML($acquittement);
    $doc_valid = $domGetAcquittement->schemaValidate(null, false, $dest_hprim->display_errors);
    if ($doc_valid) {
      $echange_hprim->statut_acquittement = $domGetAcquittement->getStatutAcquittementPatient();
    }
    $echange_hprim->acquittement_valide = $doc_valid ? 1 : 0;
      
    $echange_hprim->send_datetime = CMbDT::dateTime();
    $echange_hprim->_acquittement = $acquittement;
  
    $echange_hprim->store();
    
    CAppUI::setMsg("Message HPRIM envoyé", UI_MSG_OK);
    
    echo CAppUI::getMsg();
  }
}

