<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Mediusers\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Etablissement\Generators\CGroupsGenerator;
use Ox\Mediboard\Mediusers\CFunctions;

/**
 * Description
 */
class CFunctionsGenerator extends CObjectGenerator {
  static $mb_class = CFunctions::class;
  static $dependances = array(CGroupsGenerator::class);

  protected static $peintres;

  /** @var CFunctions  */
  protected $object;
  protected $group_id;

  /**
   * Generate a new function or return an old one
   *
   * @return CFunctions
   * @throws Exception
   */
  function generate() {
    if ($this->force) {
      $group_id = ($this->group_id) ?? CGroups::loadCurrent()->_id;
      $obj = null;
    }
    else {
      $group_id = ($this->group_id) ?: (new CGroupsGenerator())->generate()->_id;
      $where = array(
        "group_id" => "= '$group_id'"
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->text     = $this->getFunctionName();
      $this->object->type     = 'cabinet';
      $this->object->group_id = $group_id;
      $this->object->color    = $this->randomColor();

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_ERROR);
      }
      else {
        CAppUI::setMsg("CFunctions-msg-create", UI_MSG_OK);
        $this->trace(static::TRACE_STORE);
      }
    }

    return $this->object;
  }

  /**
   * @return string
   */
  protected function getFunctionName() {
    if (!self::$peintres) {
      $json           = file_get_contents(rtrim(CAppUI::conf('root_dir'), '\\/') . '/modules/populate/resources/peintres.json');
      self::$peintres = json_decode($json);
    }

    return "Cabinet " . utf8_decode(trim(self::$peintres[array_rand(self::$peintres)]));
  }

  /**
   * Get a random color
   *
   * @return string
   */
  protected function randomColor() {
    return $this->randomColorPart() . $this->randomColorPart() . $this->randomColorPart();
  }

  /**
   * @return string
   */
  protected function randomColorPart() {
    return str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
  }
}
