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
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Hospi\CObservationMedicale;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanningOp\Generators\CSejourGenerator;

/**
 * Description
 */
class CObservationGenerator extends CObjectGenerator {
  static $mb_class = CObservationMedicale::class;
  static $dependances = array(CMediusers::class);

  protected static $observations;

  /** @var CSejour */
  protected $sejour;
  /** @var CObservationMedicale */
  protected $object;

  /**
   * Initialise the generator
   *
   * @param CSejour $sejour sejour to link observations to
   *
   * @return static
   */
  public function init($sejour) {
    $this->sejour = $sejour;

    return $this;
  }

  /**
   * @inheritdoc
   * @throws Exception
   */
  public function generate() {
    if ($this->force || !$this->sejour) {
      $this->sejour = (new CSejourGenerator())->generate();
    }

    $this->object->sejour_id = $this->sejour->_id;

    $praticien            = (new CMediusersGenerator())->setGroup($this->sejour->group_id)->generate();
    $this->object->user_id = $praticien->_id;

    $this->object->degre = (rand(0, 1)) ? 'low' : 'high';
    $this->object->date  = CMbDT::getRandomDate($this->sejour->entree, $this->sejour->sortie);
    $this->object->text  = $this->getRandomContent();

    if ($msg = $this->object->store()) {
      CAppUI::setMsg($msg, UI_MSG_WARNING);
    }
    else {
      CAppUI::setMsg("CObservationMedicale-msg-create", UI_MSG_OK);
      $this->trace(static::TRACE_STORE, $this->object);
    }

    return $this->object;
  }

  /**
   * Get a random content for the consultation
   *
   * @return string
   */
  protected function getRandomContent() {
    if (!static::$observations) {
      $json = file_get_contents(rtrim(CAppUI::conf('root_dir'), '\\/') . '/modules/populate/resources/observations.json');
      static::$observations = json_decode($json);
    }

    return utf8_decode(trim(static::$observations[array_rand(static::$observations)]));
  }
}
