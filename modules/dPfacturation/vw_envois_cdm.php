<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Tarmed\CEnvoiCDM;

CCanDo::checkEdit();
$filter = new CConsultation();
$filter->_date_min = CValue::getOrSession("_date_min", CMbDT::date());
$filter->_date_max = CValue::getOrSession("_date_max", CMbDT::date());
$envoi_cdm = new CEnvoiCDM();
$envoi_cdm->filename = CValue::getOrSession("filename");
$envoi_cdm->result   = CValue::getOrSession("result");
$code                = CValue::getOrSession("code");
$envoi_cdm->statut   = CValue::getOrSession("statut", "attente");
$facture_guid        = CValue::get("facture_guid");
$view_list           = CValue::get("view_list", 0);
$page                = CValue::get("page", "0");

$envois_cdm = $total_envois = array();
$facture = null;
$total_envois = 0;
if ($view_list && !$facture_guid) {
  $ljoin = array();
  $where = array();
  $where[] = "date BETWEEN '$filter->_date_min' AND '$filter->_date_max'";
  if ($envoi_cdm->result) {
    $where["result"] = " = '$envoi_cdm->result'";
  }
  if ($envoi_cdm->filename) {
    $where[] = "filename LIKE '%$envoi_cdm->filename%'";
  }
  if ($envoi_cdm->statut) {
    $where["statut"] = " = '$envoi_cdm->statut'";
  }

  if ($code || $code == "0") {
    $ljoin["facture_messagecdm"] = "facture_messagecdm.envoi_cdm_id = facture_envoicdm.envoi_cdm_id";
    $where["facture_messagecdm.code"] = " = '$code'";
  }

  $envoi = new CEnvoiCDM();
  $envois_cdm = $envoi->loadGroupList($where, "date", "$page, 25", "envoi_cdm_id", $ljoin);
  $total_envois = $envoi->countList($where, null, $ljoin);
}
elseif ($facture_guid) {
  $facture = CMbObject::loadFromGuid($facture_guid);
  $envois_cdm = $facture->loadRefsEnvoiCDM();
}

CStoredObject::massLoadBackRefs($envois_cdm, "messages_cdm");
foreach ($envois_cdm as $_envoi) {
  $_envoi->loadTargetObject();
  $_envoi->loadRefMessages();
}

// Creation du template
$smarty = new CSmartyDP();

$smarty->assign("envois_cdm"   , $envois_cdm);
$smarty->assign("total_envois" , $total_envois);
$smarty->assign("page"         , $page);
$smarty->assign("facture"      , $facture);

if ($view_list) {

  $smarty->display("vw_envois_list_cdm.tpl");
}
else {
  $smarty->assign("filter"    , $filter);
  $smarty->assign("code"      , $code);
  $smarty->assign("envoi_cdm" , $envoi_cdm);

  $smarty->display("vw_envois_cdm");
}