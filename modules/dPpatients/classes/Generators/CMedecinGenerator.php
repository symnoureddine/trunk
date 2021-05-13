<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Generators;

use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Patients\CMedecin;

/**
 * Description
 */
class CMedecinGenerator extends CObjectGenerator {
  static $mb_class    = CMedecin::class;
  static $dependances = array();

  /** @var CMedecin $object */
  protected $object;

  /**
   * @inheritDoc
   */
  function generate() {
    $names = $this->getRandomNames(1);
    $this->object->nom  = CMbArray::get($names, 0);

    if ($msg = $this->object->store()) {
      CAppUI::setMsg($msg, UI_MSG_ERROR);
    }
    else {
      CAppUI::setMsg("CMedecin-msg-create", UI_MSG_OK);
      $this->trace(static::TRACE_STORE);
    }

    return $this->object;
  }
}
