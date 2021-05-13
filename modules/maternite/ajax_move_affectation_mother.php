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
use Ox\Core\CMbDT;
use Ox\Core\CView;
use Ox\Mediboard\Hospi\CAffectation;

$affectation_id = CView::get("affectation_id", "ref class|CAffectation");
$date_split     = CView::get("date_split", "dateTime");
$new_lit_id     = CView::get("new_lit_id", "ref class|CLit");
CView::checkin();

$tolerance         = CAppUI::gconf("dPhospi CAffectation create_affectation_tolerance");
$first_affectation = CAffectation::find($affectation_id);
$sejour            = $first_affectation->loadRefSejour();
$naissance         = $sejour->loadRefNaissance();

if ($naissance && $naissance->_id) {
  $sejour_maman       = $naissance->loadRefSejourMaman();
  $maman              = $sejour_maman->loadRefPatient();
  $affectations_maman = $sejour_maman->loadRefsAffectations();

  foreach ($affectations_maman as $_affectation) {
    $save_sortie = $_affectation->sortie;

    $modify_affectation_maman = CMbDT::addDateTime("00:$tolerance:00", $_affectation->entree) > CMbDT::dateTime();

    if ($modify_affectation_maman) {
      $_affectation->lit_id = $new_lit_id;
    }
    else {
      $_affectation->sortie = $date_split;
    }

    if ($msg = $_affectation->store()) {
      CAppUI::setMsg($msg, UI_MSG_ERROR);
    }

    if (!$modify_affectation_maman) {
      $affectation                        = new CAffectation;
      $affectation->lit_id                = $new_lit_id;
      $affectation->sejour_id             = $_affectation->sejour_id;
      $affectation->parent_affectation_id = $affectation_id;
      $affectation->entree                = $date_split;
      $affectation->sortie                = $save_sortie;

      if ($msg = $affectation->store()) {
        CAppUI::setMsg($msg, UI_MSG_ERROR);
      }
    }
  }
}

echo CAppUI::getMsg();
CApp::rip();
