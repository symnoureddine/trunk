<?php
/**
 * @package Mediboard\Hospi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreatePrestationsAndHisItemTest
 *
 * @description Test de création d'une prestation et de son item
 * @screen      PrestationsPage
 */
class CreatePrestationsAndHisItemTest extends SeleniumTestMediboard {

  public $name_prestation = "test boisson";
  public $type_hospi = "comp";
  public $name_item_prestation = "test eau";

  /** @var PrestationsPage $page */
  public $prestationsPage;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->prestationsPage = new PrestationsPage($this);
//  }

  /**
   * Création d'une prestation et de son item
   *
   * @config [CConfiguration] dPhospi prestations systeme_prestations expert
   */
  public function testCreatePrestationsAndHisItemOk() {
    $page = $this->prestationsPage;

    //create prestation
    $page->createPrestation($this->name_prestation, $this->type_hospi);
    $this->assertEquals("Prestation ponctuelle créée", $page->getSystemMessage());
    $this->assertContains($this->name_prestation, $page->getPrestationCreated());

    //create item
    $page->createItem($this->name_item_prestation);
    $this->assertEquals("Item de prestation créé", $page->getSystemMessage());
    $this->assertContains($this->name_item_prestation, $page->getItemCreated());
  }
}
