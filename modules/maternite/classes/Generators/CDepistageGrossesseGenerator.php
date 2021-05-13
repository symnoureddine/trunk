<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Maternite\CDepistageGrossesse;
use Ox\Mediboard\Maternite\CGrossesse;

/**
 * Générateur de dépistage de grossesse
 */
class CDepistageGrossesseGenerator extends CObjectGenerator {
  static $mb_class = CDepistageGrossesse::class;
  static $dependances = array(CGrossesse::class);

  /** @var CDepistageGrossesse */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $grossesse = (new CGrossesseGenerator())->setForce($this->force)->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        "grossesse_id" => "= '$grossesse->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->grossesse_id = $grossesse->_id;
      $this->object->date = "now";

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CDepistageGrossesse-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}
