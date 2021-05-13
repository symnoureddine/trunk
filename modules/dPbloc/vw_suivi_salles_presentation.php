<?php
/**
 * @package Mediboard\Bloc
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;

CCanDo::checkRead();

$blocs_ids = CValue::getOrSession("blocs_ids");
$salle_ids = CValue::get("salle_ids");

$date_suivi = CAppUI::pref("suivisalleAutonome") ?  CMbDT::date() : CValue::getOrSession("date", CMbDT::date());

$smarty = new CSmartyDP();

$smarty->assign("blocs_ids", $blocs_ids);
$smarty->assign("salle_ids", $salle_ids);
$smarty->assign("date"     , $date_suivi);

$smarty->display("vw_suivi_salles_presentation.tpl");
