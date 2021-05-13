<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CCodable;

CCanDo::checkRead();
$subject_guid = CView::get('subject_guid', 'guid class|CCodable');
$type         = CView::get('type', 'str default|ccam');
CView::checkin();

$count = 0;
if ($subject_guid) {
  /** @var CCodable $subject */
  $subject = CMbObject::loadFromGuid($subject_guid);
  switch ($type) {
    case 'ngap':
      $subject->loadRefsActesNGAP();
      $count = count($subject->_ref_actes_ngap);
      break;
    case 'ccam':
    default:
      $subject->loadRefsActesCCAM();
      $count = count($subject->_ref_actes_ccam);
  }
}

$smarty = new CSmartyDP();
$smarty->assign('count', $count);
$smarty->assign('subject_guid', $subject_guid);
$smarty->assign('type', $type);
$smarty->display('inc_acts_counter.tpl');