<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Forms\CExClassRefChecker;
use Ox\Mediboard\System\Forms\CExClass;

CCanDo::checkAdmin();

$step = CView::get('step', 'num default|100');

CView::checkin();

$ex_class   = new CExClass();
$ds         = $ex_class->getDS();
$ex_classes = $ex_class->loadList(['group_id' => $ds->prepare("= ?", CGroups::loadCurrent()->_id)]);

$ex_class_check = CExClassRefChecker::getKeys($ex_classes);

// Go to the current ex_class to check
foreach ($ex_class_check as $_key => $_info) {
  if ($_info === false || $_info['ended'] === false) {
    break;
  }
}

$ex_class_id = str_replace(CExClassRefChecker::PREFIX . '-' . CExClassRefChecker::PRE_TBL, '', $_key);

$start = (isset($_info['start'])) ? $_info['start'] : 0;
$step  = (isset($_info['step'])) ? $_info['step'] : 100;

$ref_checker = new CExClassRefChecker($ex_class_id);
$ref_checker->check($start, $step);
