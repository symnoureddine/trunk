<?php
/**
 * @package Mediboard\Ssr\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * SejourSSRTest
 *
 * @description Test sejour SSR
 * @screen      SejourSSRPage
 */
class SejourSSRTest extends SeleniumTestMediboard {
  public $patientLastname = "PatientLastname";
  public $chapterName = "kine";
  public $categoryName = "Test";
  public $etablissement = "Etablissement";
  public $elementName = "Test element";
  public $name_fct = "chir";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    if (CMbDT::isWeekend()) {
//      $this->markTestSkipped('Test class not exectuted on weekends');
//    }
//    parent::setUp();
//  }

  /**
   * Création d'un sejour SSR
   */
  public function testCreatePecSSROk() {
    $page = new SejourSSRPage($this);
    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
    $page->createSejourSSR($this->patientLastname);
    $this->assertContains("Séjour créé", $page->getSystemMessage());
  }

  /**
   * Ajout d'un soin au séjour SSR
   */
  public function testAddSoinSejour() {
    $this->markTestSkipped('FW update, need ref');
    //Création d'un élément de prescription avant d'aller sur le séjour
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");

    //Test d'ajout d'un soin au séjour
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);

    $this->assertContains("Elément ajouté", $page->getSystemMessage());
  }

  /**
   * Ajout d'un évènement dédié au séjour SSR
   */
  public function testAddEvenementDedie() {
    $this->markTestSkipped('FW update, need ref');
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "dediee");
    $this->assertContains("Evénement créé", $page->getSystemMessage());
  }

  /**
   * Test de planification sans acte
   *
   * @config [CConfiguration] ssr general use_acte_presta aucun
   */
  public function testAddEvenementNoActe() {
    $this->markTestSkipped('FW update, need ref');
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "dediee", null, false);
    $this->assertContains("Evénement créé", $page->getSystemMessage());
  }

  /**
   * Ajout d'un évènement collectif au séjour SSR
   */
  public function testAddEvenementCollectif() {
    $this->markTestSkipped('FW update, need ref');
    $page = new SejourSSRPage($this);
    //Création du premier séjour avec un soin
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);

    //Création du second séjour avec un soin
    $this->importObject("maternite/tests/Functional/data/patient_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->createSejourSSR("FATHERLASTNAME");
    $page->addSoinSejourSSR($this->categoryName, $this->elementName, false);
    $page->addEvenement($this->categoryName, $this->elementName, "collective");
    $this->assertContains("Evénement créé", $page->getSystemMessage());
  }

  /**
   * Validation d'une séance
   *
   * @param bool $annulation Annule or validate
   */
  public function testValideSeance($annulation = false) {
    $this->markTestSkipped('FW update, need ref');
    //Import et création d'un événement dédié
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "dediee");

    //Test de validation de l'évènement
    $page->switchTab('vw_kine_board');
    $page->valideSeance($annulation);

    //Double vérification
    $this->assertContains("Evénement modifié", $page->getSystemMessage());
    $this->assertEquals(1, $page->getEventCount($annulation ? "annule" : null));
  }

  /**
   * Annulation d'une séance
   */
  public function testAnnuleSeance() {
    $this->markTestSkipped('FW update, need ref');
    $this->testValideSeance(true);
  }


  /**
   * Supprime la validation ou l'annulation d'une séance
   */
  public function testEraseSeance() {
    $this->markTestSkipped('FW update, need ref');
    //Import et création d'un événement dédié
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "dediee");

    //Test de validation de l'évènement
    $page->switchTab('vw_kine_board');
    $page->valideSeance();
    $compte_avant_modif = $page->getEventCount();

    //Effacement
    $page->eraseSeance();

    //Double vérification
    $this->assertContains("Evénement modifié", $page->getSystemMessage());
    $this->assertEquals(1, $compte_avant_modif);
    $this->assertEquals(0, $page->getEventCount());
  }

  /**
   * Test de la validation d'un événment non dédié
   * La subitilité se situe dans le changement de vue du planning du rééducateur
   */
  public function testAddEvenementNondedie() {
    $this->markTestSkipped('FW update, need ref');
    //Import et création d'un événement non dédié
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");

    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "non_dediee");

    //Test de validation de l'évènement
    $page->switchTab('vw_kine_board');
    $page->valideSeance(false, true);

    //Double vérification
    $this->assertContains("Evénement modifié", $page->getSystemMessage());
    $this->assertEquals(1, $page->getEventCount());
  }

  /**
   * Vérifier la présence d'un RHS après validation d'événement
   */
  public function testCheckRHS() {
    $this->markTestSkipped('FW update, need ref');
    //Import et création d'un événement dédié
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");

    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "dediee");

    //Test de validation de l'évènement
    $page->switchTab('vw_kine_board');
    $page->valideSeance(false, false);

    //Test dans le RHS
    $page->switchTab('vw_aed_sejour_ssr');
    $result_rhs = $page->checkRHS();

    $this->assertEquals(1, $result_rhs);
  }

  /**
   * Vérification de la purge des événements hors séjour
   */
  public function testChangeDureeSejour() {
    $this->markTestSkipped('FW update, need ref');
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    //Création d'évenements
    $page->addEvenement($this->categoryName, $this->elementName, "dediee", "week");
    sleep(2);
    $compte_avant_modif = $page->getEventCount("kine");

    //Raccourcir le séjour
    $page->changeDureeSejour(3);

    //Vérification que les évenements ont bien été supprimés
    $this->assertEquals("7", $compte_avant_modif);
    $this->assertEquals("4", $page->getEventCount("kine"));
  }
}
