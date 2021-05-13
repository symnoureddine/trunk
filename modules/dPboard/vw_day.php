<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * dPboard
 */
CAppUI::requireModuleFile("dPboard", "inc_board");

$date = CView::get("date", "date default|now", true);
$prec = CMbDT::date("-1 day", $date);
$suiv = CMbDT::date("+1 day", $date);
$vue  = CView::get("vue2", "bool default|" . CAppUI::pref("AFFCONSULT", 0), true);
CView::checkin();

global $smarty;

// Variables de templates
$smarty->assign("date", $date);
$smarty->assign("prec", $prec);
$smarty->assign("suiv", $suiv);
$smarty->assign("vue", $vue);
$smarty->assign("user_id", CMediusers::get()->_id);

$smarty->display("vw_day");
