<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet\Tests\Functional\Pages;

use Ox\Core\CMbModelNotFoundException;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Tests\HomePage;

/**
 * Appointment page representation
 */
class AppointmentPage extends HomePage {
  protected $module_name = "dPcabinet";
  protected $tab_name = "edit_planning";

  /**
   * Choose the first doctor of the list
   *
   * @return CMediusers
   * @throws CMbModelNotFoundException
   */
  public function chooseDoctor(): CMediusers {
    $driver = $this->driver;

    // The first option being "", select the second option
    $first_element = $driver->byCssSelector("#editFrm_chir_id option:nth-of-type(2)");
    $first_element->click();
    // Get the value of this which becomes our mediuser id
    $mediuser_id = $first_element->attribute("value");

    return CMediusers::findOrFail($mediuser_id);
  }

  /**
   * Select a patient by name
   *
   * @param CPatient $patient
   *
   * @return void
   */
  public function choosePatient(CPatient $patient): void {
    $driver = $this->driver;

    // Find a patient using the modal
    $modal_button = $driver->byCssSelector("#add_edit_button_pat_selector");
    $modal_button->click();

    // Focus on modal
    $driver->changeFrameFocus();

    // Enter name and first name in the search modal
    $driver->byCssSelector("#patientSearch_nom")->value($patient->nom);
    $driver->byCssSelector("#patientSearch_prenom")->value($patient->prenom);
    $driver->byCssSelector("#pat_selector_search_pat_button")->click();
    $driver->byCssSelector("#list_patients tbody:nth-of-type(2) tr:nth-of-type(1) .tick")->click();

    // Get the patient id
    $driver->byCssSelector("#editFrm_patient_id")->attribute("value");
  }

  /**
   * Choose the date of the appointment
   *
   * @return void
   */
  public function chooseDate(): void {
    $driver = $this->driver;

    // Open the date selector
    $driver->byCssSelector("#addedit_planning_button_select_date")->click();

    // On click, focus on the date modal
    $driver->changeFrameFocus();

    $driver->byCssSelector("#listePlages .plage a:nth-of-type(1)")->click();

    // The first 5 rows are display and actions
    $driver->byCssSelector("#plage_list_container #listPlaces-0 tr:nth-of-type(5) .tick.validPlage")->click();
  }

  /**
   * Create the appointment by clicking on the Save button
   *
   * @return void
   */
  public function createAppointment(): void {
    $driver = $this->driver;

    // At this part, Selenium has lost the focus on the main window
    $driver->frame(null);

    // Make the new appointment
    $driver->byCssSelector("#addedit_planning_button_submitRDV")->click();
  }
}