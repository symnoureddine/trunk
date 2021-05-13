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
 * @description Diff�rents tests sur une patiente enceinte
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
   * Cr�er une nouvelle grossesse
   */
  public function testCreateNewPregnancyOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/patiente_test.xml");

    $page->createNewPregnancy($this->motherName, $this->motherFirtName);
    $this->assertEquals("Grossesse cr��e", $page->getSystemMessage());
  }

  /**
   * Ajout de quelques renseignements g�n�raux sur la m�re et le p�re
   *
   * @config [CConfiguration] maternite CGrossesse audipog 1
   */
  public function testAddGeneralInformationOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/patiente_grossesse_test.xml");
    $this->importObject("maternite/tests/Functional/data/patient_test.xml");

    $page->openModalPregnancy();
    $page->addGeneralInformation($this->fatherName, $this->fatherFirtName);
    $this->assertEquals("Grossesse modifi�e", $page->getSystemMessage());
  }

  /**
   * Cr�er un nouvel accouchement
   */
  public function testCreateNewChildBirthOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/patiente_grossesse_test.xml");

    $page->createNewChildBirth($this->motherName);

    $this->assertEquals("Intervention cr��e", $page->getSystemMessage());
  }

  /**
   * Cr�er une naissance
   *
   * @config [CConfiguration] maternite CGrossesse audipog 1
   */
  public function testCreateBirthOk() {
    $this->today = CMbDT::transform(CMbDT::date(), null, CAppUI::conf("date"));

    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/sejour_grossesse.xml");

    $page->createBirth();

    $this->assertContains("Naissance cr��e", $page->getSystemMessage());
    $this->assertContains($this->today, $page->getBirthDate());
  }

  /**
   * Ajout de quelques renseignements de d�pistages (Immuno-h�matologie, Serologie, Biochimie, Bact�riologie, H�mato-h�mostase)
   *
   * @config [CConfiguration] maternite CGrossesse audipog 1
   */
  public function testAddScreeningInformationOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/patiente_grossesse_test.xml");

    $page->openModalPregnancy();
    $page->addScreeningInformation();
    $this->assertEquals("Suivi de d�pistage cr��", $page->getSystemMessage());
  }

  /**
   * V�rification du calcul du score de Bishop en cas de d�clenchement d'accouchement
   *
   * @config [CConfiguration] maternite CGrossesse audipog 1
   */
  public function testCheckBishopScoreOk() {
    $page = $this->dashboardPage;
    $this->importObject("maternite/tests/Functional/data/sejour_grossesse_naissance.xml");

    $page->openModalPregnancy();
    $page->openModalSummaryChildBirth();

    // V�rifi� que la s�lection donne bien le r�sultat attendu
    $this->assertEquals(8, $page->checkBishopScore(2, 4, 3, 1, 3));
    $this->assertEquals(5, $page->checkBishopScore(2, 2, 2, 2, 2));
    $this->assertEquals(11, $page->checkBishopScore(4, 3, 3, 2, 4));
  }
}