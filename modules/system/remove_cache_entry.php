<?php 
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Core\DSHM;
use Ox\Core\SHM;

CCanDo::checkAdmin();
$type  = CView::get("type", "enum notNull list|shm|dshm");
$key   = CView::get("key", "str notNull");
CView::checkin();

$key = str_replace('\\\\', '\\', $key);

$job_done = false;
switch ($type) {
  default:
  case "shm":
    $job_done = SHM::rem($key);
    break;
  case "dshm":
    $job_done = DSHM::rem($key);
    break;
}

$job_done ?
  CAppUI::setMsg("System-msg-Cache entry removed", UI_MSG_OK) :
  CAppUI::setMsg("System-error-Error during suppression", UI_MSG_ERROR);

echo CAppUI::getMsg();