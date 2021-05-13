<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbArray;
use Ox\Tests\HomePage;

/**
 * Ressources page representation
 */
class RessourcesPage extends HomePage {

  protected $module_name = "cabinet";
  protected $tab_name = "vw_ressources";

  function testCreateRessource($params) {
    $driver = $this->driver;

    $params = array_map("utf8_encode", $params);

    $libelle     = CMbArray::get($params, "libelle");
    $description = CMbArray::get($params, "description");
    $color       = CMbArray::get($params, "color");
    $actif       = CMbArray::get($params, "actif");

    $driver->byCss("button.new")->click();

    $form = "editRessource";

    $driver->byId($form . "_libelle")->sendKeys($libelle);
    $driver->byId($form . "_description")->sendKeys($description);

    $driver->byCss("div.sp-replacer")->click();

    $driver->byXPath("//span[contains(@title, '$color')]")->click();
    $driver->byCss("button.sp-choose")->click();

    $driver->byId($form . "_actif_$actif")->click();

    $driver->byCss("button.save")->click();

    return trim(utf8_decode($driver->byXPath("//div[@id='ressources_area']//td[contains(text(), '$libelle')]")->getText()));
  }

  function testCreatePlageRessource($params) {
    $driver = $this->driver;

    $this->switchTab("vw_plages_ressources");

    $params = array_map("utf8_encode", $params);

    $debut   = CMbArray::get($params, "debut");
    $fin     = CMbArray::get($params, "fin");
    $libelle = CMbArray::get($params, "libelle");

    $driver->byCss("button.new")->click();

    $form = "editPlage";

    $driver->byId($form . "_libelle")->sendKeys($libelle);

    $driver->byId($form . "_debut_da")->click();
    $driver->byXPath("//div[@class='datepickerControl']//td[contains(text(), '$debut')]")->click();
    $driver->byXPath("//div[@class='datepickerControl']//button[@class='tick']")->click();

    $driver->byId($form . "_fin_da")->click();
    $driver->byXPath("//div[@class='datepickerControl']//td[contains(text(), '$fin')]")->click();
    $driver->byXPath("//div[@class='datepickerControl']//button[@class='tick']")->click();

    $driver->byCss("button.save")->click();

    return utf8_decode(
      trim(
        $driver->byXPath("//div[@id='planning_ressources']//div[@class='vertical_inverse'][contains(text(), '$libelle')]")->getText()
      )
    );
  }
}