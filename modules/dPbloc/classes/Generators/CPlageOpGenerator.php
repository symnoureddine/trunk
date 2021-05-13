<?php
/**
 * @package Mediboard\Bloc
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Bloc\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Bloc\CPlageOp;
use Ox\Mediboard\Bloc\CSalle;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;

/**
 * Description
 */
class CPlageOpGenerator extends CObjectGenerator {
  static $mb_class = CPlageOp::class;
  static $dependances = array(CSalle::class);
  static $ds = array();

  /** @var CPlageOp */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $salle = (new CSalleGenerator())->generate();
    $mediusers = (new CMediusersGenerator())->generate();

    $this->object = new CPlageOp();
    $this->object->salle_id = $salle->_id;

    $start_date              = '-' . CAppUI::conf("populate CPlageOp_date_max") . ' years';
    $end_date                = '-' . CAppUI::conf("populate CPlageOp_date_min") . ' years';
    $this->object->date      = CMbDT::getRandomDate($start_date, $end_date, 'Y-m-d');
    $this->object->chir_id = $mediusers->_id;

    if (rand(0, 1)) {
      $this->object->debut = "09:00:00";
      $this->object->fin = "12:00:00";
    }
    else {
      $this->object->debut = "14:00:00";
      $this->object->fin = "18:00:00";
    }

    if ($msg = $this->object->store()) {
      CAppUI::setMsg($msg, UI_MSG_WARNING);
    }
    else {
      CAppUI::setMsg("CPlageOp-msg-create", UI_MSG_OK);
    }

    return $this->object;
  }
}