<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CCodable;
use Ox\Mediboard\Ccam\CCodageCCAM;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();

$praticien_id   = CView::get('praticien_id', 'ref class|CMediusers');
$codable_class  = CView::get('codable_class', 'str');
$codable_id     = CView::get('codable_id', 'ref meta|codable_class');
$date           = CView::get('date', 'date');
$lock           = CView::get('lock', 'bool default|1');
$export         = CView::get('export', 'bool default|0');

CView::checkin();

$user = CMediusers::get();
/** @var CCodable $codable */
$codable = CMbObject::loadFromGuid("$codable_class-$codable_id");
if (!$date) {
  $date = CMbDT::date($codable->_datetime);
}
$codage = CCodageCCAM::get($codable, $praticien_id, 1, $date);

if (CAppUI::gconf("dPccam codage lock_codage_ccam") != 'password' && $codable_class != 'CSejour') {
  $codage = new CCodageCCAM();
  $codage->praticien_id = $praticien_id;
  $codage->codable_class = $codable_class;
  $codage->codable_id = $codable_id;
  $codages = $codage->loadMatchingList();

  foreach ($codages as $_codage) {
    $_codage->locked = $lock;
    $_codage->store();
  }

  $msg = $lock ? 'CCodageCCAM-msg-codage_locked' : 'CCodageCCAM-msg-codage_unlocked';
  CAppUI::setMsg($msg, UI_MSG_OK);
  echo CAppUI::getMsg();
  CApp::rip();
}

$smarty = new CSmartyDP();
$smarty->assign('praticien_id', $praticien_id);
$smarty->assign('praticien', $codage->loadPraticien());
$smarty->assign('codable_class', $codable->_class);
$smarty->assign('codable_id', $codable->_id);
$smarty->assign('date', $date);
$smarty->assign('lock', $lock);
$smarty->assign('export', $export);

if (CAppUI::gconf("dPccam codage lock_codage_ccam") == 'password' && $user->_id != $codage->praticien_id) {
  $smarty->assign('askPassword', true);
}
else {
  $smarty->assign('askPassword', false);
}

$smarty->display('inc_check_lock_codage.tpl');