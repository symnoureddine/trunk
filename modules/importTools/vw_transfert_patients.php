<?php 
/**
 * @package Mediboard\ImportTools
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbString;
use Ox\Core\CSmartyDP;

CCanDo::checkRead();

$file = file_get_contents(__DIR__."/transfert_patients.md");
if ($file) {
  echo "<div>" . CMbString::markdown($file) . "</div>";
}

// Affichage des boutons après les explications
$smarty = new CSmartyDP();
$smarty->display("vw_transfert_patients.tpl");