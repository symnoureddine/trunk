<?php
/**
 * @package Mediboard\Hospi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Hospi\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Hospi\CCible;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanningOp\Generators\CSejourGenerator;

/**
 * Description
 */
class CCibleGenerator extends CObjectGenerator {
  /** @var CCible */
  protected $object;
  protected $sejour;
  protected $cible_name;

  static $mb_class = CCible::class;
  static $dependances = array(CSejour::class);

  /**
   *  Generate an object
   *
   * @return CMbObject
   * @throws Exception
   */
  function generate() {
    if ($this->force || !$this->sejour) {
      $this->sejour = (new CSejourGenerator())->setForce($this->force)->generate();
    }

    $this->object->sejour_id   = $this->sejour->_id;
    $this->object->libelle_ATC = $this->cible_name;
    $this->object->loadMatchingObjectEsc();

    if (!$this->object || !$this->object->_id) {
      $this->object->datetime = CMbDT::dateTime();
      try {
        if ($msg = $this->object->store()) {
          CAppUI::setMsg($msg, UI_MSG_WARNING);
        }
        else {
          CAppUI::setMsg("CCible-msg-create", UI_MSG_OK);
        }
      }
      catch (Exception $e) {
        CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);
      }
    }

    return $this->object;
  }

  /**
   * Init the generator
   *
   * @param CSejour $sejour     Sejour to generate transmissions for
   * @param string  $cible_name Name for the CCible item
   *
   * @return static
   */
  function init($sejour, $cible_name) {
    $this->sejour     = $sejour;
    $this->cible_name = $cible_name;

    return $this;
  }
}
