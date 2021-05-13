<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\Events\SWF;

use Ox\AppFine\Server\CEvenementMedical;
use Ox\Core\CMbObject;
use Ox\Interop\Hl7\CHL7v2Exception;
use Ox\Interop\Hl7\CHL7v2Segment;
use Ox\Interop\Hl7\Events\CHL7v2Event;
use Ox\Interop\Hl7\Segments\CHL7v2SegmentAIG;
use Ox\Interop\Hl7\Segments\CHL7v2SegmentAIL;
use Ox\Interop\Hl7\Segments\CHL7v2SegmentRGS;
use Ox\Interop\Ihe\CIHE;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Classe CHL7v2EventSIU
 * Scheduled Workflow
 */
class CHL7v2EventSIU extends CHL7v2Event implements CHL7EventSIU {

  /** @var string */
  public $event_type = "SIU";

  /**
   * Construct
   *
   * @return CHL7v2EventSIU
   */
  function __construct() {
    parent::__construct();
    
    $this->profil    = "SWF";
    $this->msg_codes = array ( 
      array(
        $this->event_type, $this->code, "{$this->event_type}_{$this->code}"
      )
    );
    $this->transaction = CIHE::getSWFTransaction($this->code);
  }

  /**
   * @see parent::build()
   */
  function build($object) {
    parent::build($object);
        
    // Message Header 
    $this->addMSH();
  }

  /**
   * MSH - Represents an HL7 MSH message segment (Message Header)
   *
   * @return void
   * @throws CHL7v2Exception
   */
  function addMSH() {
    $MSH = CHL7v2Segment::create("MSH", $this->message);
    $MSH->build($this);
  }

  /**
   * SCH - Represents an HL7 SCH segment (Scheduling Activity Information)
   *
   * @param CMbObject $appointment Appointment
   *
   * @return void
   * @throws CHL7v2Exception
   */
  function addSCH(CMbObject $appointment) {
    $SCH = CHL7v2Segment::create("SCH", $this->message);
    $SCH->appointment = $appointment;
    $SCH->build($this);
  }

  /**
   * Represents an HL7 PID message segment (Patient Identification)
   *
   * @param CPatient $patient Patient
   *
   * @return void
   * @throws CHL7v2Exception
   */
  function addPID(CPatient $patient) {
    $PID = CHL7v2Segment::create("PID", $this->message);
    $PID->patient = $patient;
    $PID->set_id  = 1;
    $PID->build($this);
  }

  /**
   * Represents an HL7 PD1 message segment (Patient Additional Demographic)
   *
   * @param CPatient $patient Patient
   *
   * @return void
   * @throws CHL7v2Exception
   */
  function addPD1(CPatient $patient) {
    $PD1 = CHL7v2Segment::create("PD1", $this->message);
    $PD1->patient = $patient;
    $PD1->build($this);
  }

  /**
   * Represents an HL7 PV1 message segment (Patient Visit)
   *
   * @param CSejour $sejour Admit
   *
   * @return void
   * @throws CHL7v2Exception
   */
  function addPV1(CSejour $sejour) {
    $PV1 = CHL7v2Segment::create("PV1", $this->message);
    $PV1->sejour = $sejour;
    $PV1->build($this);
  }
  
  /**
   * RGS - Represents an HL7 SCH segment (Resource Group)
   *
   * @param CConsultation $appointment Appointment
   * @param int           $set_id      Set ID
   *
   * @return void
   */
  function addRGS(CConsultation $appointment, $set_id = 1) {
    /** @var CHL7v2SegmentRGS $RGS */
    $RGS = CHL7v2Segment::create("RGS", $this->message);
    $RGS->set_id = $set_id;
    $RGS->appointment = $appointment;
    $RGS->build($this);
  }
  
  /**
   * AIG - Represents an HL7 SCH segment (Appointment Information - General Resource)
   *
   * @param CConsultation $appointment Appointment
   * @param int           $set_id      Set ID
   *
   * @return void
   */
  function addAIG(CConsultation $appointment, $set_id = 1) {
    /** @var CHL7v2SegmentAIG $AIG */
    $AIG = CHL7v2Segment::create("AIG", $this->message);
    $AIG->set_id = $set_id;
    $AIG->appointment = $appointment;
    $AIG->build($this);
  }

  /**
   * AIL - Represents an HL7 AIL segment (Appointment Information - Location Resource)
   *
   * @param CConsultation $appointment Appointment
   * @param int           $set_id      Set ID
   *
   * @return void
   * @throws CHL7v2Exception
   */
  function addAIL(CConsultation $appointment, $set_id = 1) {
    /** @var CHL7v2SegmentAIL $AIL */
    $AIL = CHL7v2Segment::create("AIL", $this->message);
    $AIL->set_id = $set_id;
    $AIL->appointment = $appointment;
    $AIL->build($this);
  }
}