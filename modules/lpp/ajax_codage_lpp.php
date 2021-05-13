<?php
/**
 * @package Mediboard\Lpp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Ccam\CCodable;
use Ox\Mediboard\Lpp\CActeLPP;

CCanDo::checkRead();

$object_id    = CValue::get('object_id');
$object_class = CValue::get('object_class');

/** @var CCodable $codable */
$codable = CMbObject::loadFromGuid("$object_class-$object_id");

$codable->loadRefsActesLPP();
foreach ($codable->_ref_actes_lpp as $_acte) {
  $_acte->loadRefExecutant();
  $_acte->_ref_executant->loadRefFunction();
}

$acte_lpp = CActeLPP::createFor($codable);

$smarty = new CSmartyDP();
$smarty->assign('codable', $codable);
$smarty->assign('acte_lpp', $acte_lpp);
$smarty->display('inc_codage_lpp');
