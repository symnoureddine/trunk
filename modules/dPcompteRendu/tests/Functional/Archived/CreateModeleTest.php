<?php
/**
 * @package Mediboard\CompteRendu\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateModeleTest
 *
 * @description Test creation of a document's modele
 * @screen      ModelesPage
 */
class CreateModeleTest extends SeleniumTestMediboard {
  public $chir_name = "CHIR Test";
  /** @var ModelesPage */
  public $page;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//
//    $this->page = new ModelesPage($this);
//    $this->importObject("dPcompteRendu/tests/Functional/data/modele_test.xml");
//    $this->importObject("dPcompteRendu/tests/Functional/data/liste_choix_test.xml");
//  }

  /**
   * Création d'un modèle de document appelé NomModele pour l'utilisateur CHIR Test avec comme classe Séjour
   */
  public function testCreateModeleOk() {
    $page = $this->page;
    $page->createModele($this->chir_name);

    $this->assertEquals("Document/modèle créé", $page->getSystemMessage());
  }

  /**
   * Insertion d'un champ de modèle
   */
  public function testAddFieldOk() {
    $page = $this->page;

    $field = "Général - date du jour";
    $page->switchTab("vw_modeles");
    $field_inserted = $page->testAddChamp($field);

    $this->assertEquals("[$field]", $field_inserted);
  }

  /**
   * Insertion d'une liste de choix dans un modèle
   */
  public function testAddListeChoix() {
    $page = $this->page;

    $liste = "ListeChoix";
    $page->switchTab("vw_modeles");
    $liste_inserted = $page->testAddListeChoix($liste);

    $this->assertEquals("[Liste - $liste]", $liste_inserted);
  }

  /**
   * Insertion d'une zone de texte libre dans un modèle
   */
  public function testAddZoneTexteLibre() {
    $page = $this->page;

    $zone = "Zone";
    $page->switchTab("vw_modeles");
    $zone_inserted = $page->testAddZoneTexteLibre($zone);

    $this->assertEquals("[[Texte libre - $zone]]", $zone_inserted);
  }

  /**
   * Insertion d'une image dans un modèle
   */
  public function testAddImage() {
    $page = $this->page;

    $page->switchTab("vw_modeles");
    $image = $page->testAddImage();

    $this->assertTrue(preg_match("/\?m=(files|dPfiles)&(amp;)?raw=(fileviewer|thumbnail)([^\>]*)&(amp;)?(document_guid|file_id)=(CFile-)?([0-9]+)([^\s'\"]*)/", $image) === 1);
  }
}