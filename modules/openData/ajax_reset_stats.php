<?php 
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;

CCanDo::checkAdmin();

$cache = new Cache('CMedecinImport', 'stats', Cache::OUTER | Cache::DISTR);
$cache->rem();

CAppUI::js("ImportMedecins.updateStats()");

CApp::rip();