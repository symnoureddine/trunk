<?php
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CValue;

$sejour_id = CValue::getOrSession("sejour_id");

if ($sejour_id) {
  echo CApp::fetch("soins", "ajax_vw_dossier_sejour", array("sejour_id" => $sejour_id, "popup" => 1));
}