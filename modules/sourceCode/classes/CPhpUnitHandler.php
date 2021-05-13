<?php
/**
 * @package Mediboard\Erp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode;

use Ox\Core\Handlers\ObjectHandler;
use Ox\Core\CStoredObject;
use Ox\Tests\TestMediboard;

/**
 * Handler sur les TestsMediboard
 */
class CPhpUnitHandler extends ObjectHandler {

  /**
   * @param CStoredObject $object
   *
   * @return bool
   */
  public static function isHandled(CStoredObject $object) {
    return (bool)!$object->_old->_id;
  }

  /**
   * @param CStoredObject $object
   *
   * @return void
   */
  public function onAfterStore(CStoredObject $object) {
    if (!$this->isHandled($object)) {
      return;
    }
    TestMediboard::addStoredObject($object);
  }

  /**
   * @param CStoredObject $object
   *
   * @return void
   */
  function onAfterMerge(CStoredObject $object) {
    $this->onAfterStore($object);
  }
}
