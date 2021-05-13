<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CConsultationCategorie;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();

$user          = CMediusers::get();
$sel_cabinet   = CView::getRefCheckEdit("selCabinet", "ref class|CFunctions default|$user->function_id", true);
$sel_praticien = CView::getRefCheckEdit("selPrat", "ref class|CMediusers default|");
$categorie_id  = CView::get('categorie_id', 'ref class|CConsultationCategorie');
CView::checkin();

$droit = true;

// Vérification du droit sur le cabinet
$cabinet = new CFunctions();
$list_cabinets = $cabinet->loadSpecialites(PERM_EDIT);
$praticien = new CMediusers();
$list_praticiens = $praticien->loadPraticiens(PERM_EDIT, null, null, false, false);

if (!array_key_exists($sel_cabinet, $list_cabinets)) {
  $droit = false;
}

// Chargement de la categorie
$categorie = new CConsultationCategorie();

if ($categorie_id) {
  $categorie->load($categorie_id);
}

if(!$categorie->_id ||
  (!array_key_exists($categorie->function_id, $list_cabinets) &&
    !array_key_exists($categorie->praticien_id, $list_praticiens))) {
  $categorie = new CConsultationCategorie();
  $categorie->valueDefaults();
}
else {
  $sel_cabinet = $categorie->function_id;
  $sel_praticien = $categorie->praticien_id;
}
$cabinet->load($sel_cabinet);
$praticien->load($sel_praticien);

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("droit"     , $droit);
$smarty->assign("selCabinet", $cabinet);
$smarty->assign("selPrat"   , $praticien);
$smarty->assign("categorie" , $categorie);

$smarty->display("inc_edit_categorie.tpl");
