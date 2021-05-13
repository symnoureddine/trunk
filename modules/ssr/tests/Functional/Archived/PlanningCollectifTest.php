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
 * @description Test param�trage planning collectif
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
   * Cr�ation d'une trame
   *
   * @pref edit_planning_collectif 1
   */
  public function testCreateTrame() {
    $this->page->createTrame($this->nameTrame);
    $this->assertContains("Trame de s�ance collective cr��e", $this->page->getSystemMessage());
  }

  /**F
   * Cr�ation d'une plage dans la trame
   *
   * @config [CConfiguration] ssr general use_acte_presta aucun
   * @pref edit_planning_collectif 1
   */
  public function testCreatePlage() {
    $this->page->createTrame($this->nameTrame);
    $this->page->createPlage($this->nameTrame);
    $this->assertContains("Plage collective cr��e", $this->page->getSystemMessage());
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
    $this->assertContains("Ev�nement cr��", $this->page->getSystemMessage());
  }
}
