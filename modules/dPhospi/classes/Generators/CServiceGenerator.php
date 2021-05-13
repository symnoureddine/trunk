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
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Etablissement\Generators\CGroupsGenerator;
use Ox\Mediboard\Hospi\CService;

/**
 * Description
 */
class CServiceGenerator extends CObjectGenerator {
  static $mb_class = CService::class;
  static $dependances = array(CGroups::class);

  /** @var CService */
  protected $object;
  protected $group_id;

  /**
   * @inheritdoc
   */
  function generate() {
    if ($this->force) {
      $obj = null;
      $this->group_id = CGroups::loadCurrent()->_id;
    }
    else {
      $this->group_id = $this->group_id ?: (new CGroupsGenerator())->generate()->_id;

      $where = array(
        "group_id" => "= '$this->group_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->group_id = $this->group_id;
      $this->object->nom = "CService-name"; // TODO names for services
      // TODO type_sejour ?

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CService-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}