<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Mediboard\System\CErrorLog;
use Ox\Mediboard\System\CErrorLogData;

CCanDo::checkAdmin();

$error_log = new CErrorLog();
$ds = $error_log->getDS();
$query = "TRUNCATE {$error_log->_spec->table}";
$ds->exec($query);

$error_log_data = new CErrorLogData();
$ds = $error_log->getDS();
$query = "TRUNCATE {$error_log_data->_spec->table}";
$ds->exec($query);

CAppUI::stepAjax("Journaux d'erreur vidés");