<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CValue;
use Ox\Mediboard\PlanningOp\CSejour;

$codable_guid = CValue::post('codable_guid');
$actes        = explode('|', CValue::post('actes', ''));

/** @var CSejour $codable */
$codable = CMbObject::loadFromGuid($codable_guid);
$date = CValue::post('date', $codable->sortie);

if ($codable->_id) {
  $codable->loadRefsActesNGAP();

  foreach ($actes as $_acte_id) {
    if (array_key_exists($_acte_id, $codable->_ref_actes_ngap)) {
      $_acte = $codable->_ref_actes_ngap[$_acte_id];

      $days = CMbDT::daysRelative($_acte->execution, CMbDT::format($date, '%Y-%m-%d 00:00:00')) + 1;
      $_date = CMbDT::dateTime(null, $_acte->execution);
      $time = CMbDT::time(null, $_acte->execution);

      for ($i = 1; $i <= $days; $i++) {
        $_acte->execution = CMbDT::date("+$i DAYS", $_date) . " $time";
        $_acte->_id = null;
        $_acte->store();
      }
    }
  }
}