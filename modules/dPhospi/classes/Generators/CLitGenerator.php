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
use Ox\Mediboard\Hospi\CLit;

/**
 * Description
 */
class CLitGenerator extends CObjectGenerator {
  static $mb_class = CLit::class;
  static $dependances = array(CChambre::class);
  static $ds = array();

  /** @var CLit */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $chambre = (new CChambreGenerator())->setGroup($this->group_id)->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        "chambre_id" => "= '$chambre->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->chambre_id = $chambre->_id;
      $this->object->nom = "CLit-" . $chambre->_id;

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CLit-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}