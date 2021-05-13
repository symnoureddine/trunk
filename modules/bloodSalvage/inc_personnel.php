<?php

use Ox\Core\CMbDT;
use Ox\Mediboard\Personnel\CAffectationPersonnel;

/**
 * @package Mediboard\BloodSalvage
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

function loadAffected(&$blood_salvage_id, &$list_nurse_sspi, &$tabAffected, &$timingAffect) {
  $affectation               = new CAffectationPersonnel();
  $affectation->object_class = "CBloodSalvage";
  $affectation->object_id    = $blood_salvage_id;
  $tabAffected               = $affectation->loadMatchingList();

  foreach ($tabAffected as $key => $affect) {
    if (array_key_exists($affect->personnel_id, $list_nurse_sspi)) {
      unset($list_nurse_sspi[$affect->personnel_id]);
    }
    $affect->loadRefPersonnel()->loadRefUser();
  }

  // Initialisations des tableaux des timings
  foreach ($tabAffected as $key => $affectation) {
    $timingAffect[$affectation->_id]["_debut"] = array();
    $timingAffect[$affectation->_id]["_fin"]   = array();
  }

  // Remplissage des tableaux des timings
  foreach ($tabAffected as $id => $affectation) {
    foreach ($timingAffect[$affectation->_id] as $key => $value) {
      for ($i = -10; $i < 10 && $affectation->$key !== null; $i++) {
        $timingAffect[$affectation->_id][$key][] = CMbDT::time("$i minutes", $affectation->$key);
      }
    }
  }
}
