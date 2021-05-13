<?php
/**
 * @package Mediboard\Hospi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;

/**
 * Test de paramétrage des plans d'étage
 */
class PlanEtagePage extends HomePage {
  protected $tab_name = "vw_plan_etage";
  protected $module_name =  "dPhospi";

  /**
   * Test de drag and drop d'un lit
   *
   * @return array
   */
  public function testMoveLit() {
    $driver = $this->driver;

    $driver->byCss("#planEtage_services_id option:nth-child(2)")->click();
    $driver->byCss("form[name='planEtage'] button")->click();

    $driver->waitForAjax('plan_etage_service');

    $selector = "#list-chambres-non-placees div.chambre.draggable";
    $lits_before = $driver->findElements(WebDriverBy::cssSelector($selector));

    $lit = $driver->byCss("#list-chambres-non-placees div.chambre.draggable");
    $case = $driver->byCss("#grille td.conteneur-chambre");
    $driver->action()->dragAndDrop($lit, $case)->perform();

    // Wait for page loading
    $driver->waitForAjax("plan_etage_service");

    $lits_after = $driver->findElements(WebDriverBy::cssSelector($selector));
    return array(count($lits_before) - 1, count($lits_after));
  }
}