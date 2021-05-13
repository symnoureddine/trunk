<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <methodesetoutils@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Module;

use Ox\Core\CacheManager;
use Ox\Core\CAppUI;
use Ox\Core\Composer\CComposerScript;
use Ox\Core\DSHM;
use Ox\Core\SHM;

/**
 * Main abstract periodical task
 */
class CAbstractModuleCache implements IModuleCache {
  protected $module;
  protected $shm_patterns;
  protected $dshm_patterns;

  /**
   * @inheritdoc
   */
  public function clear(): void {
    if (!empty($this->shm_patterns)) {
      foreach ($this->shm_patterns as $shm_pattern) {
        $count = SHM::remKeys(trim($shm_pattern . '*'));
        CacheManager::output("module-system-msg-cache-removal", CAppUI::UI_MSG_OK, $count, $shm_pattern);
      }
    }

    // Only SHM clearing in composer context
    if (CComposerScript::$is_running) {
      return;
    }

    if (!empty($this->dshm_patterns)) {
      foreach ($this->dshm_patterns as $dshm_pattern) {
        // Namespaced classes have double \ in redis
        $dshm_pattern = str_replace('\\', '\\\\', $dshm_pattern);

        $count = DSHM::remKeys(trim($dshm_pattern . '*'));
        CacheManager::output("module-system-msg-cache-removal", CAppUI::UI_MSG_OK, $count, $dshm_pattern);
      }
    }

    $this->clearSpecialActions();
  }

  /**
   * @inheritdoc
   */
  public function clearSpecialActions(): void {
    /* Override in subclass if necessary */
  }

  /**
   * @inheritdoc
   */
  public function setSHMPatterns($shm_patterns) {
    $this->shm_patterns = $shm_patterns;
  }

  /**
   * @inheritdoc
   */
  public function getSHMPatterns() {
    return $this->shm_patterns;
  }

  /**
   * @inheritdoc
   */
  public function setDSHMPatterns($dshm_patterns) {
    $this->dshm_patterns = $dshm_patterns;
  }

  /**
   * @inheritdoc
   */
  public function getDSHMPatterns() {
    return $this->dshm_patterns;
  }
}
