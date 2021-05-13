<?php
/**
 * @package Mediboard\Patients\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * DirectivesAnticipeesPatientTest
 *
 * @description Test advance directives of the patient.
 * @screen      DossierPatientPage
 */
class DirectivesAnticipeesPatientTest extends SeleniumTestMediboard {

  /** @var DossierPatientPage $page */
  public $page = null;

  public $patientLastname = "WAYNE";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new DossierPatientPage($this);
//    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");
//  }

  /**
   * Créé des directives anticipées et vérifier dans le plan de soins l'affichage de la dernière directive saisie.
   *
   * @config [CConfiguration] soins synthese show_directives 1
   */
  public function testDirectivesAnticipeesOk() {
    $page                = $this->page;
    $corresp_names       = array('Maboule', 'Tran');
    $corresp_first_names = array('Henri', 'Kyusa');

    $page->searchPatientByName($this->patientLastname);
    $page->createCorrespondence();
    $this->assertContains("Correspondant enregistré", $page->getSystemMessage());
    $page->createMedicalCorrespondents(false, $corresp_names, $corresp_first_names);
    $this->assertContains("Correspondant médical créé", $page->getSystemMessage());
    $page->closeModal();
    $page->selectMedicalCorrespondents(3, 1);
    $this->assertContains("Correspondant ajouté", $page->getSystemMessage());
    $page->selectMedicalCorrespondents(4, 2);
    $this->assertContains("Correspondant ajouté", $page->getSystemMessage());
    $page->createDirectivesAnticipees();
    $page->switchTab("vw_idx_patients");
    $page->openModalDossierSoins($this->patientLastname);
    $this->assertTrue($page->checkLastDirectiveInSynthesis());
  }
}
