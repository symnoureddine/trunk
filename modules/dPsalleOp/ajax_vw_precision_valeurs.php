<?php
/**
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkEdit();
$geste_perop_precision_id = CView::get("geste_perop_precision_id", "ref class|CGestePeropPrecision");
CView::checkin();

$precision = new CGestePeropPrecision();
$precision->load($geste_perop_precision_id);

$valeurs = $precision->loadRefValeurs();

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("valeurs", $valeurs);
$smarty->display("inc_vw_precision_valeurs");
