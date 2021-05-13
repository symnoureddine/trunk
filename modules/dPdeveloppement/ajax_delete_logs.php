<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbString;
use Ox\Core\CView;

CCanDo::checkEdit();

CView::checkin();

$filename      = CApp::getPathMediboardLog();
$log_size_deca = CMbString::toDecaBinary(0);

@unlink($filename);

CAppUI::callbackAjax("Control.Tabs.setTabCount", "log-tab", $log_size_deca);

