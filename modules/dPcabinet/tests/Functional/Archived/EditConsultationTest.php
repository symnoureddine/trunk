<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * EditConsultationTest
 *
 * @description Test l'ajout d'informations dans la consultation
 *
 * @screen ConsultationsPage
 */
class EditConsultationTest extends SeleniumTestMediboard {
  /** @var ConsultationsPage $page */
  public $consultationPage;

  public $chir_name = 'CHIR Test';
  public $patientLastname = 'PatientLastname';

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->consultationPage = new ConsultationsPage($this);
//    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
//  }

  /**
   * Teste la saisie des informations dans la checklist
   */
  public function testcheckedInfosChecklist() {
    $this->consultationPage->switchModule("dPpatients");
    $patientsPage = new DossierPatientPage($this, false);
    $patientsPage->searchPatientByName($this->patientLastname);
    $patientsPage->createConsultationImmediate($this->chir_name);

    $this->consultationPage->switchTab("vw_info_checklist");
    $ckecklist_page = new InfoChecklistPage($this, false);
    $ckecklist_page->testCreateInfoChecklistOk("NomInfo");

    $this->consultationPage->switchTab("edit_consultation");
    $this->consultationPage->checkedInfosChecklist("NomInfo");
    $this->assertContains('Item créé', $this->consultationPage->getSystemMessage());
  }

  /**
   * Création d'une prescription externe dans la consultation
   *
   * @config [CConfiguration] dPcabinet CPrescription view_prescription_externe 1
   */
  public function testCreatePrescriptionOk() {
    $this->consultationPage->switchModule("dPpatients");
    $patientsPage = new DossierPatientPage($this, false);
    $patientsPage->searchPatientByName($this->patientLastname);
    $patientsPage->createConsultationImmediate($this->chir_name);

    // Create the prescription
    $this->consultationPage->createPrescription();
    $this->assertEquals("Prescription créée", $this->consultationPage->getSystemMessage());
  }
}