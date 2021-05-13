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
use Ox\Mediboard\Hospi\CAffectation;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Mediusers\Generators\CFunctionsGenerator;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\Personnel\CAffectationPersonnel;
use Ox\Mediboard\PlanningOp\Generators\CSejourGenerator;

/**
 * Description
 */
class CAffectationGenerator extends CObjectGenerator {
  static $mb_class = CAffectation::class;
  static $dependances = array(CService::class);
  static $ds = array();

  public static $mode_entrees = ["N", "8", "7", "6", "0"];
  public static $provenance = ["1", "2", "3", "4", "5", "6", "7", "8", "R"];
  public static $mode_sorties = ["0", "4", "5", "6", "7", "8", "9"];

  /** @var CAffectationPersonnel */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $service  = (new CServiceGenerator())->generate();
    $lit      = (new CLitGenerator())->generate();
    $sejour   = (new CSejourGenerator())->setForce($this->force)->generate();
    $function = (new CFunctionsGenerator())->generate();
    $mediuser = (new CMediusersGenerator())->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        "sejour_id" => "= '$sejour->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->service_id   = $service->_id;
      $this->object->lit_id       = $lit->_id;
      $this->object->sejour_id    = $sejour->_id;
      $this->object->function_id  = $function->_id;
      $this->object->praticien_id = $mediuser->_id;
      $this->object->entree       = $sejour->entree;
      $this->object->sortie       = $sejour->sortie;
      $this->object->mode_entree  = CAffectationGenerator::$mode_entrees[array_rand(CAffectationGenerator::$mode_entrees)];
      $this->object->provenance   = CAffectationGenerator::$provenance[array_rand(CAffectationGenerator::$provenance)];
      $this->object->mode_sortie  = CAffectationGenerator::$mode_sorties[array_rand(CAffectationGenerator::$mode_sorties)];

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CAffectation-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}
