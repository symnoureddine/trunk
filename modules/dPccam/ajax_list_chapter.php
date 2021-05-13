<?php 
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CCCAM;

CCanDo::checkRead();

$parent = CView::get('parent', 'str');

CView::checkin();

$chapters = CCCAM::getChapters($parent);

CApp::json($chapters);