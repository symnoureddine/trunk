<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CClassMap;
use Ox\Core\Module\CAbstractModuleCache;
use Ox\Core\Module\CModule;
use Ox\Mediboard\System\Cron\CCronJobLog;

/**
 * Description
 */
class CModuleCacheSystem extends CAbstractModuleCache {
  public $module = 'system';

  protected $shm_patterns = [
    CApp::class,
    CClassMap::class,
    "class-paths",
  ];

  protected $dshm_patterns = [
    "index-",
    CCronJobLog::class,
  ];

  /**
   * @inheritdoc
   */
  public function clearSpecialActions(): void {
    parent::clearSpecialActions();
    CAppUI::stepAjax('CModuleAction-msg-%d deleted cached ID|pl', UI_MSG_OK, CModuleAction::clearCacheIDs());
    CModule::clearCacheRequirements();
  }
}
