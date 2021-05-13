<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbDT;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Cabinet\Tests\Functional\Pages\AppointmentPage;
use Ox\Mediboard\Patients\CPatient;
use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateAppointmentTest
 *
 * @description Try and create an appointment
 * @screen      AppointmentPage
 */
class CreateAppointmentTest extends SeleniumTestMediboard {
  /** @var AppointmentPage */
  public $appointment_page;

  /**
   * @inheritdoc
   */
  public function setUpPage() {
    parent::setUpPage();
    $this->appointment_page = new AppointmentPage($this);
  }

  /**
   * Make an appointment
   */
  public function testCreateAppointment() {
    // Prepare objects
    $appointment_page = $this->appointment_page;
    $patient          = $this->getRandomObjects(CPatient::class, 1);

    // Choose the doctor
    $mediuser = $appointment_page->chooseDoctor();

    // Choose the patient
    $appointment_page->choosePatient($patient);

    // Prepare other objects
    $plageconsult          = new CPlageconsult();
    $plageconsult->date    = CMbDT::date();
    $plageconsult->debut   = "08:00:00";
    $plageconsult->fin     = "20:00:00";
    $plageconsult->freq    = "00:15:00";
    $plageconsult->chir_id = $mediuser->_id;
    $plageconsult->loadMatchingObject();
    if (!$plageconsult->_id) {
      $plageconsult->store();
    }

    // Choose the date
    $appointment_page->chooseDate();

    // Create the appointment
    $appointment_page->createAppointment();

    // Final assert
    $this->assertEquals("Consultation créée", $appointment_page->getSystemMessage());
  }
}