<?php
/**
 * @package Mediboard\Personnel
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Personnel\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Bloc\Generators\CPlageOpGenerator;
use Ox\Mediboard\Personnel\CAffectationPersonnel;
use Ox\Mediboard\Personnel\CPersonnel;

/**
 * Description
 */
class CAffectationPersonnelGenerator extends CObjectGenerator {
  static $mb_class = CAffectationPersonnel::class;
  static $dependances = array(CPersonnel::class);
  static $ds = array();

  /** @var CAffectationPersonnel */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $plage_op  = (new CPlageOpGenerator())->generate();
    $personnel = (new CPersonnelGenerator())->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        "personnel_id" => "= '$personnel->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->personnel_id = $personnel->_id;
      $this->object->realise      = random_int(0, 1);
      $this->object->setObject($plage_op);

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CAffectationPersonnel-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}