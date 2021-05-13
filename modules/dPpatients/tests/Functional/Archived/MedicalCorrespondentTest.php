<?php
/**
 * @package Mediboard\Patients\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * MedicalCorrespondentTest
 *
 * @description Test creation of a medical correspondent of the patient
 * @screen      DossierPatientPage
 */
class MedicalCorrespondentTest extends SeleniumTestMediboard {

  /** @var $page DossierPatientPage */
  public $page;

  public $patientLastname = "PATIENTLASTNAME";

  /**
   * @inheritdoc
   */
  public function setUpPage() {
    $this->page = new DossierPatientPage($this);
  }

  /**
   * Cr�ation d'un correspondant m�dical et association � un patient.
   * V�rification de son existance dans un mod�le de document.
   */
  public function testMedicalCorrespondentAndCheckIntoModelOk() {
    $this->importObject("dPpatients/tests/Functional/data/patient_consult_test.xml");
    $this->importObject("dPcompteRendu/tests/Functional/data/modele_patient_test.xml");
    $page                = $this->page;
    $corresp_names       = array('Maboule', 'Tran');
    $corresp_first_names = array('Henri', 'Kyusa');
    $field_corresp       = "M�decin correspondant - medecine generale - Nom Pr�nom";

    $page->searchPatientByName($this->patientLastname);
    $page->createMedicalCorrespondents(true, $corresp_names, $corresp_first_names);
    $this->assertContains("Correspondant m�dical cr��", $page->getSystemMessage());
    $page->closeModal();
    $page->selectMedicalCorrespondents(3, 1);
    $this->assertContains("Correspondant ajout�", $page->getSystemMessage());
    $page->selectMedicalCorrespondents(4, 2);
    $this->assertContains("Correspondant ajout�", $page->getSystemMessage());

    $mPage  = new ModelesPage($this);
    $result = $mPage->addCompleteFieldAndSaveIt($field_corresp);

    $this->assertEquals("[$field_corresp]", $result['field']);
    $this->assertContains("Document/mod�le modifi�", $result['msg']);

    $page->switchModule("dPpatients");
    $page->switchTab("vw_full_patients");

    $page->checkCorrespondentNameInModel();
    $name_model = $page->selectFieldInModel($field_corresp);
    $fullname   = strtoupper($corresp_names[1]) . " " . $corresp_first_names[1];
    $this->assertEquals($fullname, $name_model);
  }
}
