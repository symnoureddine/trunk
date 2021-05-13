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
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\Patients\CEvenementPatient;

/**
 * Class CEvenementPatientGenerator
 *
 * @package Ox\Mediboard\Populate
 */
class CEvenementPatientGenerator extends CObjectGenerator {
  static $mb_class = CEvenementPatient::class;
  static $dependances = array(CMediusers::class, CDossierMedical::class);

  /** @var CEvenementPatient */
  protected $object;

  /**
   * @inheritdoc
   */
  public function generate(): CEvenementPatient
  {
    $dossier_medical = (new CDossierMedicalGenerator())->generate();
    $owner= (new CMediusersGenerator())->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = ["dossier_medical_id" => "= '$dossier_medical->_id'"];
      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->creation_date  = "now";
      $this->object->date           = "now";
      $this->object->libelle        = "Evt Generated";
      $this->object->praticien_id   = $owner->_id;
      $this->object->owner_id       = $owner->_id;
      $this->object->dossier_medical_id = $dossier_medical->_id;

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      } else {
        CAppUI::setMsg("CEvenementPatient-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}
