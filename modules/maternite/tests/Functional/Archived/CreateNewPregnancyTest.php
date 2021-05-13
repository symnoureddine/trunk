<?php
/**
 * @package Mediboard\Maternite\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateNewPregnancyTest
 *
 * @description Différents tests sur une patiente enceinte
 * @screen      DashboardPage
 */
class CreateNewPregnancyTest extends SeleniumTestMediboard {
  /** @var DashboardPage */
  public $dashboardPage;
  public $motherName = "MOTHERLASTNAME";
  public $motherFirtName = "Motherfirstname";
  public $fatherName = "FATHERLASTNAME";
  public $fatherFirtName = "Fatherfirstname";
  public $today;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->dashboardPage = new DashboardPage($this);
//  }

  /**
   * Créer une nouvelle grossesse
   */
  public function testCreateNewPregnancyOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/patiente_test.xml");

    $page->createNewPregnancy($this->motherName, $this->motherFirtName);
    $this->assertEquals("Grossesse créée", $page->getSystemMessage());
  }

  /**
   * Ajout de quelques renseignements généraux sur la mère et le père
   *
   * @config [CConfiguration] maternite CGrossesse audipog 1
   */
  public function testAddGeneralInformationOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/patiente_grossesse_test.xml");
    $this->importObject("maternite/tests/Functional/data/patient_test.xml");

    $page->openModalPregnancy();
    $page->addGeneralInformation($this->fatherName, $this->fatherFirtName);
    $this->assertEquals("Grossesse modifiée", $page->getSystemMessage());
  }

  /**
   * Créer un nouvel accouchement
   */
  public function testCreateNewChildBirthOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/patiente_grossesse_test.xml");

    $page->createNewChildBirth($this->motherName);

    $this->assertEquals("Intervention créée", $page->getSystemMessage());
  }

  /**
   * Créer une naissance
   *
   * @config [CConfiguration] maternite CGrossesse audipog 1
   */
  public function testCreateBirthOk() {
    $this->today = CMbDT::transform(CMbDT::date(), null, CAppUI::conf("date"));

    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/sejour_grossesse.xml");

    $page->createBirth();

    $this->assertContains("Naissance créée", $page->getSystemMessage());
    $this->assertContains($this->today, $page->getBirthDate());
  }

  /**
   * Ajout de quelques renseignements de dépistages (Immuno-hématologie, Serologie, Biochimie, Bactériologie, Hémato-hémostase)
   *
   * @config [CConfiguration] maternite CGrossesse audipog 1
   */
  public function testAddScreeningInformationOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/patiente_grossesse_test.xml");

    $page->openModalPregnancy();
    $page->addScreeningInformation();
    $this->assertEquals("Suivi de dépistage créé", $page->getSystemMessage());
  }

  /**
   * Vérification du calcul du score de Bishop en cas de déclenchement d'accouchement
   *
   * @config [CConfiguration] maternite CGrossesse audipog 1
   */
  public function testCheckBishopScoreOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/sejour_grossesse_naissance.xml");

    $page->openModalPregnancy();
    $page->openModalSummaryChildBirth();

    // Vérifié que la sélection donne bien le résultat attendu
    $this->assertEquals(8, $page->checkBishopScore(2, 4, 3, 1, 3));
    $this->assertEquals(5, $page->checkBishopScore(2, 2, 2, 2, 2));
    $this->assertEquals(11, $page->checkBishopScore(4, 3, 3, 2, 4));
  }
}