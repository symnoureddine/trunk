<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\Patients\CPatient;

/**
 * Class CDossierMedicalGenerator
 *
 * @package Ox\Mediboard\Populate
 */
class CDossierMedicalGenerator extends CObjectGenerator {
  static $mb_class = CDossierMedical::class;
  static $dependances = array(CPatient::class);

  /** @var CDossierMedical */
  protected $object;

  /**
   * @inheritdoc
   */
  public function generate(): CDossierMedical
  {
    $patient = (new CPatientGenerator())->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = ["object_id" => "= '$patient->_id'", "object_class" => "= 'CPatient'"];
      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->object_id    = $patient->_id;
      $this->object->object_class = $patient->_class;

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      } else {
        CAppUI::setMsg("CDossierMedical-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}
