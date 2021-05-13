<?php
/**
 * @package Mediboard\Hospi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

global $m;

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CDoObjectAddEdit;
use Ox\Core\CMbDT;
use Ox\Core\Module\CModule;
use Ox\Core\CView;
use Ox\Mediboard\Hospi\CAffectation;

$entree   = CView::post("entree", "dateTime");
$sortie   = CView::post("sortie", "dateTime");
$callback = CView::post("callback", "str");

CView::checkin();

$tolerance          = CAppUI::gconf("dPhospi CAffectation create_affectation_tolerance");
$modify_affectation = CMbDT::addDateTime("00:$tolerance:00", $entree) > $_POST["_date_split"];

// Modifier la première affectation, affectation du lit si la tolérance de création d'affectation n'est pas atteint
$do = new CDoObjectAddEdit("CAffectation");

if ($modify_affectation) {
  $_POST["lit_id"] = $_POST["_new_lit_id"];
}
else {
  $_POST["entree"] = $entree;
  $_POST["sortie"] = $_POST["_date_split"];
}

$do->redirect      = null;
$do->redirectStore = null;
$do->doIt();

$first_affectation = $do->_obj;

// Créer la seconde si la tolérance est dépassé
if (!$modify_affectation) {
  $do = new CDoObjectAddEdit("CAffectation", "affectation_id");

  $_POST["ajax"]           = 1;
  $_POST["entree"]         = $_POST["_date_split"];
  $_POST["sortie"]         = $sortie;
  $_POST["lit_id"]         = $_POST["_new_lit_id"];
  $_POST["affectation_id"] = null;

  $do->doSingle(false);
}

// Gérer le déplacement du ou des bébés si nécessaire
if (CModule::getActive("maternite")) {
  /** @var CAffectation[] $affectations_enfant */
  $affectations_enfant = $first_affectation->loadBackRefs("affectations_enfant");

  if ($affectations_enfant) {
    foreach ($affectations_enfant as $_affectation) {
      $save_sortie = $_affectation->sortie;

      $modify_affectation_enfant = CMbDT::addDateTime("00:$tolerance:00", $_affectation->entree) > $_POST["_date_split"];

      if ($modify_affectation_enfant) {
        $_affectation->lit_id = $_POST["_new_lit_id"];
      }
      else {
        $_affectation->sortie = $_POST["_date_split"];
      }

      if ($msg = $_affectation->store()) {
        CAppUI::setMsg($msg, UI_MSG_ERROR);
      }

      if (!$modify_affectation_enfant) {
        $affectation                        = new CAffectation;
        $affectation->lit_id                = $_POST["_new_lit_id"];
        $affectation->sejour_id             = $_affectation->sejour_id;
        $affectation->parent_affectation_id = $do->_obj->_id;
        $affectation->entree                = $_POST["_date_split"];
        $affectation->sortie                = $save_sortie;

        if ($msg = $affectation->store()) {
          CAppUI::setMsg($msg, UI_MSG_ERROR);
        }
      }
    }
  }
  else {
    $naissance = $first_affectation->_ref_sejour->loadRefNaissance();

    if ($naissance && $naissance->_id) {
      $sejour_maman = $naissance->loadRefSejourMaman();
      $maman        = $sejour_maman->loadRefPatient();
      $affectations_maman = $sejour_maman->loadRefsAffectations();

      CAppUI::callbackAjax("Placement.associatedAffectation", $first_affectation->_id, $_POST["_date_split"], $_POST["_new_lit_id"], $tolerance);
    }
  }
}

// La possible réinstanciation du $do fait perdre le callback
$do->callBack = $callback;

$do->doRedirect();
