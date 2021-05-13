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
use Ox\Mediboard\Lpp\CLPPCode;

CCanDo::checkRead();

$code = CLPPCode::load(CValue::get('code'));
$code->loadLastPricing();
$code->loadPricings();
$code->loadParent();
$code->loadCompatibilities();
$code->loadIncompatibilities();

$smarty = new CSmartyDP();
$smarty->assign('code', $code);
$smarty->display('inc_code');