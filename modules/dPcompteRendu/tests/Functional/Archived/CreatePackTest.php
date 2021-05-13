<?php
/**
 * @package Mediboard\CompteRendu\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreatePackTest
 *
 * @description Test creation of a pack
 * @screen      PacksPage
 */
class CreatePackTest extends SeleniumTestMediboard {
  public $chir_name = "CHIR Test";
  /** @var PacksPage */
  public $page;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//
//    $this->page = new PacksPage($this);
//    $this->importObject("dPcompteRendu/tests/Functional/data/modele_test.xml");
//  }

  /**
   * Création d'un pack de modèle
   */
  public function testCreatePackModele() {
    $page = $this->page;

    $messages = $page->testCreatePackModele("Pack");

    $this->assertEquals("Pack créé", $messages[0]);
    $this->assertEquals("Modèle"   , $messages[1]);
  }
}