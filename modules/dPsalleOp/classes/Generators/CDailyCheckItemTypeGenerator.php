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
use Ox\Mediboard\SalleOp\CDailyCheckItemCategory;
use Ox\Mediboard\SalleOp\CDailyCheckItemType;

/**
 * CDebiteurOXGenerator
 */
class CDailyCheckItemTypeGenerator extends CObjectGenerator {
  static $mb_class = CDailyCheckItemType::class;
  static $dependances = array(CDailyCheckItemCategory::class);

  /** @var CDailyCheckItemType */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $category = (new CDailyCheckItemCategoryGenerator())->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        "active" => "= '1'",
        "category_id" => "= '$category->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->active = "1";
      $this->object->category_id = $category->_id;
      $this->object->title = "Type 1";
      $this->object->index = 1;

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CDailyCheckItemType-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}