<?php
/**
 * @package Mediboard\Pmsi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * PmsiTest
 *
 * @description Test creation of various object in "Pmsi" module
 * @screen      PmsiPage
 */
class PmsiTest extends SeleniumTestMediboard {

  /** @var $page PmsiPage */
  public $page = null;

  // UM
  public $libelle_um    = "01A";
  public $mode_hospi_um = "HC";
  public $nb_lits_um    = 10;

  // UF
  public $type_sejour = "comp";
  public $libelle_uf  = "Heb 01A";

  // Patient
  public $patientLastname  = "WAYNE";
  public $patientFirstname = "bruce";
  public $actesName        = "AAFA001";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//
//    $this->page = new PmsiPage($this);
//  }

  /**
   * Configuration des unités médicales PMSI et création d'une unité fonctionnelle associée à une UM
   * Affectation de UF à un service et vérification que UF soit rattaché au service 1
   *
   * @config [CConfiguration] atih uf uf_pmsi hebergement
   */
  public function testUMAssociatedToUFOk() {
    $pagePmsi = $this->page;
    $pagePmsi->switchTab("configure");
    $pagePmsi->accessControlTab("Config-UM");

    // UM
    $pagePmsi->createUM($this->libelle_um, $this->mode_hospi_um, $this->nb_lits_um);
    $this->assertEquals("Unité médicale créée", $pagePmsi->getSystemMessage());

    // UF
    $pagePmsi->switchModule("dPhospi");
    $pagePmsi->switchTab("vw_idx_infrastructure");
    $pagePmsi->accessControlTab("UF");
    $pagePmsi->createUF($this->type_sejour, $this->libelle_um, $this->libelle_uf);
    $this->assertEquals("Unité fonctionnelle créée", $pagePmsi->getSystemMessage());

    // Associate UF to a service
    $pagePmsi->accessControlTab("services");
    $pagePmsi->associateUFToService($this->libelle_um);
    $this->assertEquals("Affectation d'UF créée", $pagePmsi->getSystemMessage());
    $pagePmsi->closeModal();

    $pagePmsi->accessControlTab("UF");
    $this->assertTrue($pagePmsi->checkStatUFAndService());
  }

  /**
   * Création d'un fichier RSS et Vérification du groupage PMSI
   * Si le groupage ne fonctionne pas, verifier si les droits sur le bin linux est en chmod 755
   */
  public function testCreateRSSFileAndCheckGroupageOk() {
    $this->importObject("dPpmsi/tests/Functional/data/patient_sejour.xml");
    $pagePmsi = $this->page;

    $pagePmsi->switchTab("vw_dossier_pmsi");
    $pagePmsi->createRSS($this->patientLastname, $this->patientFirstname, $this->actesName);
    $this->assertEquals("RUM modifié", $pagePmsi->getSystemMessage());
    $this->assertTrue($pagePmsi->checkIfActeFromSejourToRUM($this->actesName));
    $this->assertEquals("018", $pagePmsi->checkRUMVersion());
    $this->assertTrue($pagePmsi->generateGroupage());
    $pagePmsi->validateGroupage();
    $this->assertEquals("Traitement du dossier effectué", $pagePmsi->getSystemMessage());
  }

  /**
   * Teste si la vue du codage CCAM est visible dans le PMSI, et si il est possible de coder des actes
   */
  public function testCodagePMSI() {
    $this->importObject('dPpmsi/tests/Functional/data/codage_pmsi.xml');

    $this->page->openDossier('FOO', 'BAR');
    $this->page->viewActesInterv();
    $this->assertTrue($this->page->isCodeAdded('MJFA015'));
    $this->page->codeActe('YYYY001');
    $this->assertContains('Acte CCAM créé', $this->page->getSystemMessage());
  }

  /**
   * Vérifier que les diagnostics dossier d'un séjour SSR sont bien visibles depuis le volet PMSI.
   */
  public function testDiagnosesFolderFromPMSIOk() {
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $pagePmsi = $this->page;
    $patientLastname = "PATIENTLASTNAME";
    $patientFirstname = "Patientfirstname";
    $codes = array("A020", "A070", "A080", "A071");

    $pagePmsi->switchTab("vw_dossier_pmsi");
    $pagePmsi->openDossier($patientLastname, $patientFirstname);
    $pagePmsi->viewActes();
    $messages = $pagePmsi->checkCodesCIMIsEmpty();

    $this->assertContains("Aucun Code CIM", $messages["msg"]);
    $this->assertEquals(5, $messages["count"]);

    $pagePmsi->fillInCodesDiagnosesRHS($codes);
    $number = $pagePmsi->checkCodesCIMAreAdded($codes);
    $this->assertEquals(count($codes), $number);
  }

  /**
   * Teste si il est possible de verrouiller/déverrouiller les codages CCAM dans le PMSI
   */
  public function testVerrouillageCodagePMSI() {
    $this->importObject('dPpmsi/tests/Functional/data/codage_pmsi.xml');

    $this->page->openDossier('FOO', 'BAR');
    $this->page->viewActesInterv();
    $this->assertTrue($this->page->isCodeAdded('MJFA015'));
    $this->page->lockCodage();
    $this->assertContains('Codage CCAM validé', $this->page->getSystemMessage());
    $this->page->unlockCodage();
    $this->assertContains('Codage CCAM invalidé', $this->page->getSystemMessage());
  }
}
