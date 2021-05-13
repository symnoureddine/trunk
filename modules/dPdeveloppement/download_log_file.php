<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;

CCanDo::checkAdmin();

ob_end_clean();

$file = CApp::getPathMediboardLog();

header("Content-Type: text/html");
header("Content-Length: " . filesize($file));
header("Content-Disposition: attachment; filename=mediboard.log");

readfile($file);

CApp::rip();