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
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;

/**
 * Class CPlageconsultGenerator
 *
 * @package Ox\Mediboard\Populate
 */
class CPlageconsultGenerator extends CObjectGenerator {
  static $mb_class = CPlageconsult::class;
  static $dependances = array(CMediusers::class);

  /** @var CPlageconsult */
  protected $object;
  /** @var CMediusers */
  protected $praticien;

  /**
   * Generate a CPlageconsult
   *
   * @return CPlageconsult
   * @throws Exception
   */
  function generate() {
    /* Generate a CMediuser if none has been set */
    if (!$this->praticien || !$this->praticien->_id || !$this->praticien instanceof CMediusers) {
      $this->praticien = (new CMediusersGenerator())->generate();
    }

    $this->object->chir_id = $this->praticien->_id;

    if (!$this->object->date) {
      $start_date  = CMbDT::dateTime('-' . CAppUI::conf('populate CSejour_max_years_start') . ' YEARS');
      $end_date    = CMbDT::dateTime('+' . CAppUI::conf('populate CSejour_max_years_end') . ' YEARS');
      $this->object->date = CMbDT::getRandomDate($start_date, $end_date, 'Y-m-d');

      /* Ensure that the random date is not a holiday, because that will block the creation of the CPlageconsult */
      while (CMbDT::isHoliday($this->object->date)) {
        $this->object->date = CMbDT::getRandomDate($start_date, $end_date, 'Y-m-d');
      }
    }

    $this->object->debut = '08:00:00';
    $this->object->fin = '18:00:00';
    $this->object->freq = '00:15:00';

    if ($msg = $this->object->store()) {
      CAppUI::stepAjax($msg, UI_MSG_ERROR);
    }
    else {
      CAppUI::setMsg("CPlageconsult-msg-create", UI_MSG_OK);
      $this->trace(static::TRACE_STORE);
    }

    return $this->object;
  }

  /**
   * Set the date of the plage
   *
   * @param string $date The date
   *
   * @return static
   */
  public function setDate($date) {
    $this->object->date = $date;

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
}