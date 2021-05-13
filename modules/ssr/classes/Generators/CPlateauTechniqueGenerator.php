<?php
/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ssr\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Etablissement\Generators\CGroupsGenerator;
use Ox\Mediboard\Ssr\CPlateauTechnique;

/**
 * Générateur de plateau Technique
 */
class CPlateauTechniqueGenerator extends CObjectGenerator {
  static $mb_class = CPlateauTechnique::class;
  static $dependances = array(CGroups::class);

  /** @var CPlateauTechnique */
  protected $object;
  protected $group_id;

  /**
   * @inheritdoc
   */
  function generate() {
    if ($this->force) {
      $this->group_id = CGroups::loadCurrent()->_id;
      $obj = null;
    }
    else {
      $this->group_id = $this->group_id ?: (new CGroupsGenerator())->generate()->_id;

      $where = array(
        "group_id" => "= '$this->group_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->group_id = $this->group_id;
      $this->object->nom = "Plateau Test";
      $this->object->type = "ssr";

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CPlateauTechnique-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}