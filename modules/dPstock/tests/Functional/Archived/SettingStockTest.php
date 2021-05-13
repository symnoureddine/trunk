<?php
/**
 * @package Mediboard\Stock\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * SettingStockTest
 *
 * @description Test create settings of stocks
 * @screen      SettingStockPage
 */
class SettingStockTest extends SeleniumTestMediboard {

  // catalogue
  public $companyName   = "Unilever";
  public $code          = "U001";
  public $placeName     = "Lieu pharmacie";
  public $description   = "Lieu de la Pharmacie";
  public $endowmentName = "Pack urgent";
  public $categoryName  = "Livret etablissement";
  public $medicament    = "paracetamol test";

  /**
   * Création des paramétrages des stocks - Société & Emplacement
   */
  public function testCreateSettingsCompanyAndPlaceOk() {
    $page = new SettingStockPage($this);

    //Création des paramétrages des stocks - Société
    $page->createCompany($this->companyName, $this->code);
    $this->assertEquals("Société créée", $page->getSystemMessage());
    $this->assertContains($this->companyName, $page->getCompanyName($this->companyName));

    //Création des paramétrages des stocks - Emplacement
    $page->createPlace($this->placeName, $this->description);
    $this->assertEquals("Emplacement créé", $page->getSystemMessage());
    $this->assertContains("Pharmacie - Etablissement", $page->getPlaceName());
  }

  /**
   * Création des paramétrages des stocks - Dotation et vérification via la pagination du service de la dotation
   */
  public function testCreateSettingsEndowmentOk() {
    $page = new SettingStockPage($this);

    // create category
    $page->createCategory($this->categoryName);
    $this->assertEquals("Catégorie créée", $page->getSystemMessage());

    // create product
    $page->createProduct($this->medicament, $this->categoryName);
    $this->assertEquals("Produit créé", $page->getSystemMessage());

    // create endowment
    $page->createEndowment($this->endowmentName);
    $this->assertEquals("Dotation créée", $page->getSystemMessage());
    $this->assertContains('Service 1', $page->getFindEndowmentServiceName($this->endowmentName));

    // add product
    $page->addProductToEndowment($this->medicament);
    $this->assertEquals("Dotation de produit créée", $page->getSystemMessage());
  }
}
