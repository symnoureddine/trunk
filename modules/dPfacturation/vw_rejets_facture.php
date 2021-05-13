<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Facturation\CFactureRejet;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkEdit();
$praticien_id = CValue::getOrSession("praticien_id");
$file_name    = CValue::getOrSession("file_name");
$num_facture  = CValue::getOrSession("num_facture");
$date         = CValue::getOrSession("date");
$statut       = CValue::getOrSession("statut");
$name_assurance= CValue::getOrSession("name_assurance");

$where = array();
$where["praticien_id"] = " = '$praticien_id'";
if ($num_facture) {   $where["num_facture"]  = " LIKE '%$num_facture%'";}
if ($file_name) {     $where[".file_name"] = " LIKE '%$file_name%'";}
if ($date) {          $where["date"]  = " = '$date'";}
if ($statut) {        $where["statut"]  = " = '$statut'";}
if ($name_assurance){ $where["name_assurance"]  = " = '$name_assurance'";}

$rejet = new CFactureRejet();
$order = "num_facture, date";
$rejets = $rejet->loadList($where, $order, null, "facture_rejet_id");

foreach ($rejets as $_rejet) {
  /* @var CFactureRejet $_rejet*/
  $_rejet->readXML(null, true);
  if (!$_rejet->num_facture) {
    unset($rejets[$_rejet->_id]);
    continue;
  }
  $_rejet->loadRefPatient();
}

// Liste des chirurgiens
$user = new CMediusers();
$listChir =  $user->loadPraticiens(PERM_EDIT);

// Creation du template
$smarty = new CSmartyDP();

$smarty->assign("rejets"              , $rejets);

$smarty->display("vw_list_rejets");
