<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Forms\CExClassRefChecker;

CCanDo::checkAdmin();

$ex_class_id = CView::post('ex_class_id', 'ref class|CExClass notNull');
$start       = CView::post('start', 'num default|0');
$step        = CView::post('step', 'num default|100');

CView::checkin();

$ref_checker = new CExClassRefChecker($ex_class_id);
$ref_checker->check($start, $step);

echo CAppUI::getMsg();
