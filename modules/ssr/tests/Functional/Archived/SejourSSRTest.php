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
   * Cr�ation d'un sejour SSR
   */
  public function testCreatePecSSROk() {
    $page = new SejourSSRPage($this);
    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
    $page->createSejourSSR($this->patientLastname);
    $this->assertContains("S�jour cr��", $page->getSystemMessage());
  }

  /**
   * Ajout d'un soin au s�jour SSR
   */
  public function testAddSoinSejour() {
    $this->markTestSkipped('FW update, need ref');
    //Cr�ation d'un �l�ment de prescription avant d'aller sur le s�jour
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");

    //Test d'ajout d'un soin au s�jour
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);

    $this->assertContains("El�ment ajout�", $page->getSystemMessage());
  }

  /**
   * Ajout d'un �v�nement d�di� au s�jour SSR
   */
  public function testAddEvenementDedie() {
    $this->markTestSkipped('FW update, need ref');
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "dediee");
    $this->assertContains("Ev�nement cr��", $page->getSystemMessage());
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
    $this->assertContains("Ev�nement cr��", $page->getSystemMessage());
  }

  /**
   * Ajout d'un �v�nement collectif au s�jour SSR
   */
  public function testAddEvenementCollectif() {
    $this->markTestSkipped('FW update, need ref');
    $page = new SejourSSRPage($this);
    //Cr�ation du premier s�jour avec un soin
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);

    //Cr�ation du second s�jour avec un soin
    $this->importObject("maternite/tests/Functional/data/patient_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->createSejourSSR("FATHERLASTNAME");
    $page->addSoinSejourSSR($this->categoryName, $this->elementName, false);
    $page->addEvenement($this->categoryName, $this->elementName, "collective");
    $this->assertContains("Ev�nement cr��", $page->getSystemMessage());
  }

  /**
   * Validation d'une s�ance
   *
   * @param bool $annulation Annule or validate
   */
  public function testValideSeance($annulation = false) {
    $this->markTestSkipped('FW update, need ref');
    //Import et cr�ation d'un �v�nement d�di�
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "dediee");

    //Test de validation de l'�v�nement
    $page->switchTab('vw_kine_board');
    $page->valideSeance($annulation);

    //Double v�rification
    $this->assertContains("Ev�nement modifi�", $page->getSystemMessage());
    $this->assertEquals(1, $page->getEventCount($annulation ? "annule" : null));
  }

  /**
   * Annulation d'une s�ance
   */
  public function testAnnuleSeance() {
    $this->markTestSkipped('FW update, need ref');
    $this->testValideSeance(true);
  }


  /**
   * Supprime la validation ou l'annulation d'une s�ance
   */
  public function testEraseSeance() {
    $this->markTestSkipped('FW update, need ref');
    //Import et cr�ation d'un �v�nement d�di�
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "dediee");

    //Test de validation de l'�v�nement
    $page->switchTab('vw_kine_board');
    $page->valideSeance();
    $compte_avant_modif = $page->getEventCount();

    //Effacement
    $page->eraseSeance();

    //Double v�rification
    $this->assertContains("Ev�nement modifi�", $page->getSystemMessage());
    $this->assertEquals(1, $compte_avant_modif);
    $this->assertEquals(0, $page->getEventCount());
  }

  /**
   * Test de la validation d'un �v�nment non d�di�
   * La subitilit� se situe dans le changement de vue du planning du r��ducateur
   */
  public function testAddEvenementNondedie() {
    $this->markTestSkipped('FW update, need ref');
    //Import et cr�ation d'un �v�nement non d�di�
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");

    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "non_dediee");

    //Test de validation de l'�v�nement
    $page->switchTab('vw_kine_board');
    $page->valideSeance(false, true);

    //Double v�rification
    $this->assertContains("Ev�nement modifi�", $page->getSystemMessage());
    $this->assertEquals(1, $page->getEventCount());
  }

  /**
   * V�rifier la pr�sence d'un RHS apr�s validation d'�v�nement
   */
  public function testCheckRHS() {
    $this->markTestSkipped('FW update, need ref');
    //Import et cr�ation d'un �v�nement d�di�
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");

    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    $page->addEvenement($this->categoryName, $this->elementName, "dediee");

    //Test de validation de l'�v�nement
    $page->switchTab('vw_kine_board');
    $page->valideSeance(false, false);

    //Test dans le RHS
    $page->switchTab('vw_aed_sejour_ssr');
    $result_rhs = $page->checkRHS();

    $this->assertEquals(1, $result_rhs);
  }

  /**
   * V�rification de la purge des �v�nements hors s�jour
   */
  public function testChangeDureeSejour() {
    $this->markTestSkipped('FW update, need ref');
    $page = new SejourSSRPage($this);
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page->switchTab('vw_sejours_ssr');
    $page->addSoinSejourSSR($this->categoryName, $this->elementName);
    //Cr�ation d'�venements
    $page->addEvenement($this->categoryName, $this->elementName, "dediee", "week");
    sleep(2);
    $compte_avant_modif = $page->getEventCount("kine");

    //Raccourcir le s�jour
    $page->changeDureeSejour(3);

    //V�rification que les �venements ont bien �t� supprim�s
    $this->assertEquals("7", $compte_avant_modif);
    $this->assertEquals("4", $page->getEventCount("kine"));
  }
}
