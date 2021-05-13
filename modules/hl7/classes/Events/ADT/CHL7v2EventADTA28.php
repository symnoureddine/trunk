<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\Events\ADT;

use Ox\Mediboard\Patients\CPatient;

/**
 * Class CHL7v2EventADTA28 
 * A28 - Add person information
 */
class CHL7v2EventADTA28 extends CHL7v2EventADT implements CHL7EventADTA05 {

  /** @var string */
  public $code        = "A28";

  /** @var string */
  public $struct_code = "A05";

  /**
   * Build A28 event
   *
   * @param CPatient $patient Person
   *
   * @see parent::build()
   *
   * @return void
   */
  function build($patient) {
    parent::build($patient);
    
    // Patient Identification
    $this->addPID($patient);
    
    // Patient Additional Demographic
    $this->addPD1($patient);

    if ($this->version > "2.3.1") {
      // Doctors
      $this->addROLs($patient);
    }
    
    // Next of Kin / Associated Parties
    $this->addNK1s($patient);

    // Patient Visit
    $this->addPV1();

    // Build specific segments (i18n)
    $this->buildI18nSegments($patient);
  }

  /**
   * Build i18n segements
   *
   * @param CPatient $patient Person
   *
   * @see parent::buildI18nSegments()
   *
   * @return void
   */
  function buildI18nSegments($patient) {
  }
}