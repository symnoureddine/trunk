<?php
/**
 * @package Mediboard\Bloc
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Bloc\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Bloc\CBlocOperatoire;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Etablissement\Generators\CGroupsGenerator;

/**
 * Description
 */
class CBlocOperatoireGenerator extends CObjectGenerator {
  static $mb_class = CBlocOperatoire::class;
  static $dependances = array(CGroups::class);
  static $ds = array();

  /** @var CBlocOperatoire */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    if ($this->force) {
      $obj = null;
      $group = CGroups::loadCurrent();
    }
    else {
      $group = (new CGroupsGenerator())->generate();

      $where = array(
        "group_id" => "= '$group->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->group_id = $group->_id;
      $this->object->nom = "CBloc-name"; // TODO names for blocs

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CBlocOperatoire-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}