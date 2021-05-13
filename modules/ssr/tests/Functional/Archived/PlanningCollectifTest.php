<?php
/**
 * @package Mediboard\Ssr\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * PlanningCollectifTest
 *
 * @description Test paramétrage planning collectif
 * @screen      PlanningCollectifPage
 */
class PlanningCollectifTest extends SeleniumTestMediboard {
  /** @var $page PlanningCollectifPage */
  public $page = null;

  public $nameTrame = "Trame1";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new PlanningCollectifPage($this);
//  }

  /**
   * Création d'une trame
   *
   * @pref edit_planning_collectif 1
   */
  public function testCreateTrame() {
    $this->page->createTrame($this->nameTrame);
    $this->assertContains("Trame de séance collective créée", $this->page->getSystemMessage());
  }

  /**F
   * Création d'une plage dans la trame
   *
   * @config [CConfiguration] ssr general use_acte_presta aucun
   * @pref edit_planning_collectif 1
   */
  public function testCreatePlage() {
    $this->page->createTrame($this->nameTrame);
    $this->page->createPlage($this->nameTrame);
    $this->assertContains("Plage collective créée", $this->page->getSystemMessage());
  }

  /**
   * Ajout d'un patient dans la plage collective
   *
   * @config [CConfiguration] ssr general use_acte_presta aucun
   * @pref edit_planning_collectif 1
   */
  public function testAddPatientPlage() {
    $this->importObject("ssr/tests/Functional/data/sejour_test.xml");
    $page = new SejourSSRPage($this);
    $page->addSoinSejourSSR("Test", "Test element");

    $this->page->switchTab('vw_planning_collectif');
    $this->page->createTrame($this->nameTrame);
    $this->page->createPlage($this->nameTrame);
    $this->page->addPatientPlage();
    $this->assertContains("Evénement créé", $this->page->getSystemMessage());
  }
}
