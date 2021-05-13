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

$code       = CValue::get('code');
$text       = CValue::get('text');
$chapter_id = CValue::get('chapter_id');
$start      = CValue::get('start', 0);

$codes = CLPPCode::search($code, $text, $chapter_id, null, $start, 100);
$total = CLPPCode::count($code, $text, $chapter_id);

$smarty = new CSmartyDP();
$smarty->assign('codes', $codes);
$smarty->assign('start', $start);
$smarty->assign('total', $total);
$smarty->display('inc_search_results');