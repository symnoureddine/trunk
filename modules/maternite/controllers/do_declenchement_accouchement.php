<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CView;
use Ox\Mediboard\PlanningOp\CModeEntreeSejour;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::checkRead();

$sejour_id       = CView::post("sejour_id", "ref class|CSejour");
$sejour_id_merge = CView::post("sejour_id_merge", "ref class|CSejour");
$praticien_id    = CView::post("praticien_id", "ref class|CMediusers");
$uf_soins_id     = CView::post("uf_soins_id", "ref class|CUniteFonctionnelle");
$mode_entree     = CView::post("mode_entree", "str");
$mode_entree_id  = CView::post("mode_entree_id", "ref class|CModeEntreeSejour");
$ATNC            = CView::post("ATNC", "bool");
$callback        = CView::post("callback", "str");

CView::checkin();

$sejour = new CSejour();
$sejour->load($sejour_id);

if ($sejour_id_merge) {
  $sejour_merge = new CSejour();
  $sejour_merge->load($sejour_id_merge);

  $sejour_merge->_create_affectations = false;
  $sejour_merge->_apply_sectorisation = false;

  if ($ATNC !== null) {
    $sejour_merge->ATNC = $ATNC;
  }

  foreach ($sejour_merge->loadRefsAffectations() as $_affectation) {
    $_affectation->delete();
  }

  foreach ($sejour_merge->loadRefItemsLiaisons() as $_item_liaison) {
    $_item_liaison->delete();
  }

  $duree = CMbDT::daysRelative($sejour_merge->entree, $sejour_merge->sortie);
  $sejour->sortie_prevue = CMbDT::dateTime("+$duree days", $sejour->entree_prevue);

  $sejour->_merging = CMbArray::pluck(array($sejour_merge), "_id");

  $sejour->merge(array($sejour_merge));
}

$sejour->_create_affectations = false;
$sejour->_apply_sectorisation = false;

$sejour->type           = "comp";
$sejour->praticien_id   = $praticien_id;
$sejour->uf_soins_id    = $uf_soins_id;
$sejour->mode_entree    = $mode_entree;
$sejour->mode_entree_id = $mode_entree_id;
$sejour->sortie_prevue  = CMbDT::dateTime("+4 days", $sejour->entree_prevue);
$sejour->_hour_sortie_prevue = null;
$sejour->libelle        = CAppUI::tr("CDossierPerinat-accouchement");
$sejour->charge_id      = CAppUI::conf("maternite placement charge_id_dhe", $sejour->loadRefEtablissement());
$sejour->grossesse_id   = $consult->grossesse_id;

if ($ATNC !== null) {
  $sejour->ATNC         = $ATNC;
}

if (!$sejour->mode_entree && !$sejour->mode_entree_id) {
  $use_custom_mode_entree = CAppUI::conf("dPplanningOp CSejour use_custom_mode_entree");

  $modes_entree = CModeEntreeSejour::listModeEntree($sejour->group_id);

  if ($use_custom_mode_entree && count($modes_entree)) {
    foreach ($modes_entree as $_mode_entree) {
      if ($_mode_entree->code == "8") {
        $sejour->mode_entree_id = $_mode_entree->_id;
        break;
      }
    }
  }
  else {
    $sejour->mode_entree = "8";
  }
}

$msg = $sejour->store();

$sejour->_create_affectations = true;
$sejour->_apply_sectorisation = true;

CAppUI::setMsg($msg ?: CAppUI::tr("CSejour-msg-modify"), $msg ? UI_MSG_ALERT : UI_MSG_OK);

echo CAppUI::getMsg();

CAppUI::callbackAjax($callback, $sejour->_id);
