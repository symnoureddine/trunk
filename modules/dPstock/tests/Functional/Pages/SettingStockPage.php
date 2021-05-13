<?php
/**
 * @package Mediboard\Stock\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * SettingStockPage page representation
 *
 * @package    Tests
 * @subpackage Pages
 * @author     SARL OpenXtrem <dev@openxtrem.com>
 * @license    GNU General Public License, see http://www.gnu.org/licenses/gpl.html
 * @link       http://www.mediboard.org
 */
class SettingStockPage extends HomePage {

  protected $module_name = "stock";
  protected $tab_name = "vw_idx_setup";

  /**
   * Créer une société
   *
   * @param string $companyName Company name
   * @param string $code        Company code
   *
   * @return void
   */
  public function createCompany($companyName, $code) {
    $driver = $this->driver;

    $driver->valueRetryByID("edit_societe_name", $companyName);
    $driver->byId("edit_societe_code")->sendKeys($code);
    $driver->byId("edit_societe_postal_code")->sendKeys("17");
    $driver->byCss("div.autocomplete li:nth-child(1)")->click();
    $driver->byId("edit_societe_phone")->sendKeys("0202020202");

    $driver->byId("edit_societe_contact_name")->sendKeys("Unilev");

    // save
    $driver->byCss("td#edit-societe button.submit")->click();
  }

  /**
   * Renvoie le nom de la société créée
   *
   * @param string $companyName Company name
   *
   * @return string Company name
   */
  public function getCompanyName($companyName) {
    $driver = $this->driver;

    $driver->byId("filterSociete_keywords")->sendKeys($companyName);
    $driver->byCss("div#vw_idx_societe button.search")->click();

    return $driver->byCss("td#list-societe a:nth-child(1) > span")->getText();
  }

  /**
   * Créer un emplacement
   *
   * @param string $placeName   Place name
   * @param string $description Description location
   *
   * @return void
   */
  public function createPlace($placeName, $description) {
    $driver = $this->driver;

    $this->accessControlTab("vw_idx_stock_location");

    $driver->byId("edit_stock_location_name")->sendKeys($placeName);
    $driver->byId("edit_stock_location_desc")->sendKeys($description);

    // save
    $driver->byCss("td#stock-location-form button.submit")->click();
  }

  /**
   * Renvoie le lieu de l'emplacement créé
   *
   * @return string Place name
   */
  public function getPlaceName() {
    return $this->driver->byCss("div#location-CGroups td:nth-child(3)")->getText();
  }

  /**
   * Créer une catégorie
   *
   * @param string $categoryName category name
   *
   * @return void
   */
  public function createCategory($categoryName) {
    $driver = $this->driver;

    $this->accessControlTab("vw_idx_category");

    $driver->byId("edit_category_name")->sendKeys($categoryName);

    // save
    $driver->byCss("div#vw_idx_category button.submit")->click();
  }

  /**
   * Créer un produit/médicament
   *
   * @param string $medicament   medicament name
   * @param string $categoryName category name
   *
   * @return void
   */
  public function createProduct($medicament, $categoryName) {
    $driver = $this->driver;

    $this->switchTab("vw_idx_product");

    $driver->byId("edit_product_name")->sendKeys($medicament);
    $driver->selectOptionByText("edit_product_category_id", $categoryName);
    $driver->byId("edit_product_quantity")->sendKeys(15);

    // save
    $driver->byCss("button.submit")->click();
  }

  /**
   * Créer une dotation
   *
   * @param string $endowmentName endowment name
   *
   * @return void
   */
  public function createEndowment($endowmentName) {
    $driver = $this->driver;

    $this->switchTab("vw_idx_setup");

    $this->accessControlTab("vw_idx_endowment");

    $driver->byId("edit_endowment_name")->sendKeys($endowmentName);
    $driver->byId("edit_endowment_service_id_autocomplete_view")->sendKeys("serv");
    $driver->byCss("div#edit_endowment_service_id_autocomplete_view_autocomplete li")->click();

    // save
    $driver->byCss("td#endowment-form button.submit")->click();
  }

  /**
   * Renvoie le nom du service de la dotation créée
   *
   * @param string $endowmentName endowment name
   *
   * @return string Endowment servcie name
   */
  public function getFindEndowmentServiceName($endowmentName) {
    $driver = $this->driver;
    $letter = strtoupper(substr($endowmentName, 0, 1));

    // click on the pagination
    $driver->byXPath("//div[@id='vw_idx_endowment']//div[@class='pagination ']//a[contains(text(), '$letter')]")->click();

    // get the service
    $service = $driver->byCss("div#list-endowments tr td:nth-child(2)")->getText();

    return $service;
  }

  /**
   * Ajouter un produit à une dotation
   *
   * @param string $medicament medicament name
   *
   * @return void
   */
  public function addProductToEndowment($medicament) {
    $driver = $this->driver;

    $this->accessControlTab("vw_idx_endowment");

    $driver->byId("edit_endowment_item_product_id_autocomplete_view")->sendKeys($medicament);
    $driver->byCss("div#edit_endowment_item_product_id_autocomplete_view_autocomplete li:nth-child(1)")->click();
    $driver->byId("edit_endowment_item_quantity")->sendKeys(5);

    // save
    $driver->byCss("div#vw_idx_endowment button.save")->click();
  }
}
