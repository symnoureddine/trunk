<?php
/**
 * @package Mediboard\Lpp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Lpp\CLPPDatedPricing;

CCanDo::checkRead();

$code = CValue::get('code');
$date = CValue::get('date');

$pricing = CLPPDatedPricing::loadFromDate($code, $date);

$smarty = new CSmartyDP();
$smarty->assign('pricing', $pricing);
$smarty->display('inc_pricing');