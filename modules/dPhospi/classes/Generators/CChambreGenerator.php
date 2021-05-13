<?php
/**
 * @package Mediboard\Hospi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Hospi\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Hospi\CChambre;
use Ox\Mediboard\Hospi\CService;

/**
 * Description
 */
class CChambreGenerator extends CObjectGenerator {
  static $mb_class = CChambre::class;
  static $dependances = array(CService::class);
  static $ds = array();

  /** @var CChambre */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $service = (new CServiceGenerator())->setGroup($this->group_id)->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        "service_id" => "= '$service->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->service_id = $service->_id;
      $this->object->nom = "CChambre-" . $service->_id;

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CChambre-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}