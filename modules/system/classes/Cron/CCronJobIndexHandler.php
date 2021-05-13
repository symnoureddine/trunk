<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Cron;

use Ox\Core\CApp;
use Ox\Core\CMbDT;
use Ox\Core\CMbIndexHandler;
use Ox\Core\CValue;
use Ox\Core\Mutex\CMbMutex;

/**
 * CronJob handler
 *
 * @deprecated Do not use will be remove
 */
class CCronJobIndexHandler extends CMbIndexHandler {
  /**
   * @inheritdoc
   */
  function onAfterMain() {
    return;

    if (!CApp::isCron()) {
      return;
    }

    $cron_log_id = CValue::get('execute_cron_log_id');

    // Mise à jour du statut du log suite à l'appel au script
    $cron_log = new CCronJobLog();
    $cron_log->load($cron_log_id);

    CCronJobLog::storeLog($cron_log);

    $cron_log->store();

    // Mutex aquired by execute_cronjob
    $mutex = new CMbMutex("CCronJob-{$cron_log->cronjob_id}");
    $mutex->release();
  }
}