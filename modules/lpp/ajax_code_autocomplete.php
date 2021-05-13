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

$text         = CValue::post('code');
$executant_id = CValue::post('executant_id');
$date         = CValue::post('date');

$codes = CLPPCode::search($text, $text, null, null, $date, 0, 100);

/* @todo Le code sera décommenté lorsque la base sesam-vitale sera rendue disponible */
//if ($executant_id) {
//  $user = CMediusers::get($executant_id);
//  $prestation_codes = array();
//  if ($user->spec_cpam_id) {
//    $prestation_codes = CLPPCode::getAllowedPrestationCodes($user->spec_cpam_id);
//  }
//}

foreach ($codes as $_key => $_code) {
  $_code->loadLastPricing($date);
  $_code->getQualificatifsDepense();

  if (!$_code->_last_pricing->code) {
    unset($codes[$_key]);
  }

//  if ($executant_id && !in_array($_code->_last_pricing->prestation_code, $prestation_codes)) {
//    unset($codes[$_key]);
//  }
}

$smarty = new CSmartyDP();
$smarty->assign('codes', $codes);
$smarty->display('inc_code_autocomplete');