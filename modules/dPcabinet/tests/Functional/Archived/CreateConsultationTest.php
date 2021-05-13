<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateConsultationtest
 *
 * @description Test creation of a consultation by creating a new
 *              "plage de consultation" and a new patient
 * @screen      DossierPatientPage, ConsultationPage
 */
class CreateConsultationTest extends SeleniumTestMediboard {

  /** @var ConsultationsPage $consultationPage */
  public $consultationPage = null;

  public $chir_name = "CHIR Test";
  public $patientLastname = "PatientLastname";
  public $datePlage;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->datePlage = CMbDT::date();
//    $this->consultationPage = new ConsultationsPage($this);
//  }

  /**
   * Cr�� une consultation
   */
  public function testCreateConsultationOk() {
    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
    $consultationPage = $this->consultationPage;
    $consultationPage->openPlageCreationModal($this->datePlage);
    $consultationPage->createPlageConsultation($this->chir_name, $this->datePlage);
    $consultationPage->createConsultation($this->chir_name, $this->datePlage, $this->patientLastname);
    $this->assertEquals("Consultation cr��e", $consultationPage->getSystemMessage());
  }


  /**
   * Cr�� une consultation imm�diate
   */
  public function testCreateConsultationImmediateOk() {
    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
    $consultationPage = $this->consultationPage;
    $consultationPage->switchModule("dPpatients");
    $dPpage = new DossierPatientPage($this, false);
    $dPpage->searchPatientByName($this->patientLastname);
    $dPpage->createConsultationImmediate($this->chir_name);
    $this->assertEquals("Consultation cr��e", $dPpage->getSystemMessage());
  }

  /**
   * Cr�ation d'une consultation d'anesth et liaison � une intervention.
   * V�rification du type d'anesth pr�vue et r�alis�e au niveau de la feuille de bloc
   */
  public function testCreateConsultationAnesthAndCheckTwoAnesthTypesOk() {
    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");
    $consultationPage = $this->consultationPage;

    $patientName = "WAYNE";
    $anesthName = "ANESTH";

    $consultationPage->switchModule("dPpatients");
    $dPpage = new DossierPatientPage($this, false);
    $dPpage->searchPatientByName($patientName);
    $dPpage->createConsultationImmediate($anesthName);
    $this->assertEquals("Consultation cr��e", $dPpage->getSystemMessage());

    $msg = $consultationPage->selectAnesthesiaType();
    $this->assertEquals("Consultation pr�anesth�sique modifi�e", $msg[0]);
    $this->assertEquals("Intervention modifi�e", $msg[1]);

    $types = $consultationPage->openBlockSheet();
    $this->assertEquals("Type d'anesth�sie pr�vue", $types[0]);
    $this->assertEquals("Type d'anesth�sie r�alis�e", $types[2]);
    $this->assertNotEquals($types[1], $types[3]);
  }
}