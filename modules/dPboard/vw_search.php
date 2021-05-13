<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;

CCanDo::checkRead();

$date  = CMbDT::date("-1 month");
$types = array();
if ($types = CAppUI::gconf("search active_handler active_handler_search_types")) {
  $types = explode("|", $types);
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("date", $date);
$smarty->assign("types", $types);
$smarty->display("vw_search");