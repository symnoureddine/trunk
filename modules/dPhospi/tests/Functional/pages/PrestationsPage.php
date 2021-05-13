<?php
/**
 * @package Mediboard\Hospi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Création de la prestation page representation
 */
class PrestationsPage extends HospiAbstractPage {

  protected $tab_name = "vw_prestations";

  /**
   * Création d'une prestation
   *
   * @param string $name_prestation Nom de la prestation à créer
   * @param string $type_hospi      Nom du type d'hospitalisation
   *
   * @return void
   */
  public function createPrestation($name_prestation, $type_hospi) {
    $driver = $this->driver;

    $driver->byCss("div.content button.new")->click();
    $driver->byId("edit_prestation_nom")->sendKeys($name_prestation);
    $driver->selectOptionByText("edit_prestation_type_hospi", $type_hospi);
    $driver->byId("edit_prestation___M")->click();

    $driver->byCss("td#edit_prestation button.save")->click();
  }

  /**
   * Vérification que la prestation soit bien créée
   *
   * @return string Nom de la prestation créée
   */
  public function getPrestationCreated() {
    return $this->driver->byCss("td#list_prestations  tr.prestation a:nth-child(1)")->getText();
  }

  /**
   * Création d'un item
   *
   * @param string $name_item_prestation Nom de l'item de prestation
   *
   * @return void
   */
  public function createItem($name_item_prestation) {
    $driver = $this->driver;

    $driver->byId("edit_item_nom")->sendKeys($name_item_prestation);

    $driver->byCss("div#edit_item button.save")->click();
  }

  /**
   * Vérification que l'item soit bien créé
   *
   * @return string Nom de l'item créé
   */
  public function getItemCreated() {
    return $this->driver->byCss("div#list_items  a.mediuser")->getText();
  }
}
