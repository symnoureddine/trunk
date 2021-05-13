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
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\Ssr\CPlateauTechnique;
use Ox\Mediboard\Ssr\CTechnicien;

/**
 * Générateur de technicien pour un plateau technique
 */
class CTechnicienGenerator extends CObjectGenerator {
  static $mb_class = CTechnicien::class;
  static $dependances = array(CPlateauTechnique::class, CMediusers::class);

  /** @var CTechnicien */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $plateau_technique = (new CPlateauTechniqueGenerator())->generate();
    $kine = (new CMediusersGenerator())->generate("Rééducateur");
    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        "plateau_id" => "= '$plateau_technique->_id'",
        "kine_id" => "= '$kine->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->plateau_id = $plateau_technique->_id;
      $this->object->kine_id = $kine->_id;

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CTechnicien-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}