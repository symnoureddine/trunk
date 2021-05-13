<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Forms;

use Ox\Core\Module\CAbstractModuleCache;
use Ox\Core\CAppUI;
use Ox\Mediboard\System\Forms\CExObject;

/**
 * Description
 */
class CModuleCacheForms extends CAbstractModuleCache {
  public $module = 'forms';

  /**
   * @inheritdoc
   */
  public function clearSpecialActions(): void {
    parent::clearSpecialActions();

    $count = CExObject::clearLocales();
    CAppUI::stepAjax("module-forms-msg-cache-ex_class-suppr", UI_MSG_OK, $count);
  }
}
