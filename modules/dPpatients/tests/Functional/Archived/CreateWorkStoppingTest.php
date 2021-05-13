<?php
/**
 * @package Mediboard\Patients\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateWorkStoppingTest
 *
 * @description Test the creation of the work stopping.
 * @screen      DossierPatientPage
 *
 * Needs Ameli module
 */
class CreateWorkStoppingTest extends SeleniumTestMediboard {

  /** @var DossierPatientPage $page */
  public $page = null;

  public $chir_name = "CHIR Test";
  public $patientLastname = "PATIENTLASTNAME";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new DossierPatientPage($this);
//  }

  /**
   * Cr�er un arr�t de travail
   */
  public function testCreateWorkStoppingOk() {
    $this->importObject("dPpatients/tests/Functional/data/patient_consult_test.xml");
    $page = $this->page;

    $page->searchPatientByName($this->patientLastname);
    $page->selectPatientAndConsultation();
    $page->createWorkStopping();

    $this->assertEquals("Arr�t de travail cr��", $page->getSystemMessage());
  }

  /**
   * Cr�er le premier Cerfa de la liste et verifie que le pdf soit cr��
   *
   * @config [CConfiguration] ameli CCerfa use_cerfa 1
   */
  public function testCreateCerfaAndCheckFileOk() {
    $this->importObject("dPpatients/tests/Functional/data/patient_consult_test.xml");
    $cerfa_name = "Accidents du travail et maladies professionnelles - protocole pour soins apr�s consolidation";
    $cerfa_name = utf8_encode($cerfa_name);
    $page       = $this->page;

    $page->searchPatientByName($this->patientLastname);
    $page->selectPatientAndConsultation();
    $page->createCerfa($cerfa_name);

    $this->assertContains("$cerfa_name.pdf", $page->getCerfaName());
  }

  /**
   * Cr�ation d'un avis d'arr�t de travail avec la nouvelle IHM et v�rification de quelques donn�es sur le Cerfa "arr�t de travail"
   *
   * @config [CConfiguration] ameli CCerfa use_cerfa 1
   */
  public function testCreateWorkStoppingNewIHMAndHisCerfaOk() {
    $this->importObject("dPpatients/tests/Functional/data/patient_consult_test.xml");
    $page = $this->page;

    $datas_aat = array(
      "contexte"  => array(
        "type"          => "prolongation",
        "nature"        => "TC",
        "libelle_motif" => "Cheville casse"
      ),
      "duree"     => array(
        "_duree" => 5
      ),
      "situation" => array(
        "patient_activite"          => "SA",
        "patient_employeur_nom"     => "Test societe",
        "patient_employeur_adresse" => "10 rue de la pomme",
        "patient_employeur_phone"   => 0545454545
      )
    );

    $page->searchPatientByName($this->patientLastname);
    $page->createConsultationImmediate($this->chir_name);
    $this->assertEquals("Consultation cr��e", $page->getSystemMessage());
    $page->createWorkStoppingNewIHM($datas_aat);

    // Modal AAT
    $results = $page->checkSummaryWorkStopping();
    $this->assertContains("Prolongation", $results[0]);
    $this->assertContains("Temps complet", $results[1]);
    $this->assertContains("Oui", $results[2]);
    $this->assertContains(CMbDT::format(CMbDT::date(), CAppUI::conf("date")), $results[3]);

    // Cerfa
    $results_cerfa = $page->checkDatasOnCerfa();
    $this->assertContains("true", $results_cerfa[0]);
    $this->assertContains("Cheville casse", $results_cerfa[1]);
    $this->assertContains(CMbDT::format(CMbDT::date(), "%d%m%Y"), $results_cerfa[2]);
    $this->assertContains("Test societe", $results_cerfa[3]);
  }
}