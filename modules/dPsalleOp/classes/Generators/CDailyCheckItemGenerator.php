<?php
/**
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\SalleOp\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\SalleOp\CDailyCheckItem;
use Ox\Mediboard\SalleOp\CDailyCheckItemType;
use Ox\Mediboard\SalleOp\CDailyCheckList;

/**
 * CDebiteurOXGenerator
 */
class CDailyCheckItemGenerator extends CObjectGenerator {
  static $mb_class = CDailyCheckItem::class;
  static $dependances = array(CDailyCheckList::class, CDailyCheckItemType::class);

  /** @var CDailyCheckItem */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $list = (new CDailyCheckListGenerator())->generate();
    $type = (new CDailyCheckItemTypeGenerator())->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        "list_id" => "= '$list->_id'",
        "item_type_id" => "= '$type->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->list_id = $list->_id;
      $this->object->item_type_id = $type->_id;
      $this->object->checked = "yes";

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CDailyCheckItem-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}