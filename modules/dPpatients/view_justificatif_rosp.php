<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();

$praticien_id = CView::get("praticien_id", "ref class|CMediusers");
$year_stats   = CView::get("year_stats", "str");
$type         = CView::get("type", "enum list|tamm|mb default|tamm", true);
CView::checkin();

$prat = new CMediusers();
$prat->load($praticien_id);

// Variables de templates
$smarty = new CSmartyDP();

$smarty->assign("year_stats", $year_stats);
$smarty->assign("prat", $prat);
$smarty->assign("type_mb", $type == "mb");

$smarty->display("view_justificatif_rosp");