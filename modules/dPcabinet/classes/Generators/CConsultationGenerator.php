<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\Generators\CPatientGenerator;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanningOp\Generators\CSejourGenerator;

/**
 * Class CConsultationGenerator
 *
 * @package Ox\Mediboard\Populate
 */
class CConsultationGenerator extends CObjectGenerator {
  static $mb_class = CConsultation::class;
  static $dependances = array(CMediusers::class, CFunctions::class, CPatient::class);
  static $types = ['sejour', 'normal'];

  /** @var CConsultation */
  protected $object;
  /** @var CSejour */
  protected $sejour;
  /** @var CMediusers */
  protected $praticien;
  /** @var CPatient */
  protected $patient;
  /** @var CPlageconsult */
  protected $plage;

  /**
   *  Generate a CConsultation
   *
   * @return CConsultation
   * @throws Exception
   */
  function generate() {
    /* Generate a CSejour if necessary */
    if ($this->type == 'sejour' && (!$this->sejour || !$this->sejour->_id || !$this->sejour instanceof CSejour)) {
      $this->sejour = (new CSejourGenerator())->generate();
      $this->object->sejour_id = $this->sejour->_id;
    }

    if (!$this->praticien || !$this->praticien->_id || !$this->praticien instanceof CMediusers) {
      /* Get the CMediusers from the sejour or generate a new one */
      if ($this->sejour && $this->sejour->_id && $this->sejour->praticien_id) {
        $this->praticien = $this->sejour->loadRefPraticien();
      }
      else {
        $this->praticien = (new CMediusersGenerator())->generate();
      }
    }

    if (!$this->patient || !$this->patient->_id || !$this->patient instanceof CPatient) {
      /* Get the patient from the sejour or generate a new one */
      if ($this->sejour && $this->sejour->_id && $this->sejour->patient_id) {
        $this->patient = $this->sejour->loadRefPatient();
      }
      else {
        $this->patient = (new CPatientGenerator())->generate();
      }
    }

    $this->object->patient_id = $this->patient->_id;

    /* Generate a new CPlageconsult if no one has been set */
    if (!$this->plage || !$this->plage->_id || !$this->plage instanceof CPlageconsult) {
      /* Get a random date inside the sejour, or get a completely random one */
      if ($this->sejour && $this->sejour->_id) {
        $this->object->loadRefSejour();
        $date = CMbDT::getRandomDate($this->object->_ref_sejour->entree, $this->object->_ref_sejour->sortie, 'Y-m-d');
      }
      else {
        $start_date  = CMbDT::dateTime('-' . CAppUI::conf('populate CSejour_max_years_start') . ' YEARS');
        $end_date    = CMbDT::dateTime('+' . CAppUI::conf('populate CSejour_max_years_end') . ' YEARS');
        $date = CMbDT::getRandomDate($start_date, $end_date, 'Y-m-d');
      }

      $this->plage = (new CPlageconsultGenerator())->setPraticien($this->praticien)->setDate($date)->generate();
    }

    $this->object->plageconsult_id = $this->plage->_id;

    /* Set the time of the consultation */
    $this->plage->loadRefsConsultations();
    foreach ($this->plage->loadDisponibilities() as $time => $status) {
      if ($status === 0) {
        $this->object->heure = $time;
        break;
      }
    }

    $this->object->chrono = 16;

    if ($msg = $this->object->store()) {
      CAppUI::stepAjax($msg, UI_MSG_ERROR);
    }
    else {
      CAppUI::setMsg("CConsultation-msg-create", UI_MSG_OK);
      $this->trace(static::TRACE_STORE);
    }

    (new CActeNGAPGenerator())
      ->setTargetObject($this->object)
      ->setExecutant($this->praticien)->generate();

    return $this->object;
  }

  /**
   * Set the sejour
   *
   * @param CSejour $sejour The sejour
   *
   * @return static
   */
  public function setSejour($sejour) {
    if ($sejour instanceof CSejour && $sejour->_id) {
      $this->sejour = $sejour;
      $this->object->sejour_id = $sejour->_id;

      $this->setPraticien($sejour->loadRefPraticien());
      $this->setPatient($sejour->loadRefPatient());
    }

    return $this;
  }

  /**
   * Set the practitioner of the consultation
   *
   * @param CMediusers $user The user
   *
   * @return static
   */
  public function setPraticien($user) {
    if ($user instanceof CMediusers && $user->_id) {
      $this->praticien = $user;
    }

    return $this;
  }

  /**
   * Set the patient of the consultation
   *
   * @param CPatient $patient The patient
   *
   * @return static
   */
  public function setPatient($patient) {
    if ($patient instanceof CPatient && $patient->_id) {
      $this->patient = $patient;
    }

    return $this;
  }

  /**
   * Set the plage of the consultation
   *
   * @param CPlageconsult $plage The plage
   *
   * @return static
   */
  public function setPlage($plage) {
    if ($plage instanceof CPlageconsult && $plage->_id) {
      $this->plage = $plage;
    }

    return $this;
  }
}