<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;

/**
 * Autocomplete d'un code ccam sur les codes associés
 */
CCanDo::checkRead();

$code     = CValue::get("code");
$keywords = CValue::post("keywords");

CView::enableSlave();

$code_ccam = CDatedCodeCCAM::get($code);
$code_ccam->getActesAsso($keywords, 30);

$codes = array();

foreach ($code_ccam->assos as $_code) {
  $_code_value = $_code['code'];
  $codes[$_code_value] = CDatedCodeCCAM::get($_code_value);
}

$smarty = new CSmartyDP();

$smarty->assign("codes", $codes);
$smarty->assign("keywords", $keywords);
$smarty->assign("nodebug" , true);

$smarty->display("httpreq_do_ccam_autocomplete.tpl");