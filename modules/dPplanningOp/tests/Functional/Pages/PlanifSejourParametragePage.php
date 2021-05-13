<?php
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Paramétrage page representation
 */
class PlanifSejourParametragePage extends PlanifSejourAbstractPage {
  protected $tab_name = "vw_parametrage";

  /**
   * Création d'un mode de traitement
   *
   * @param string $name_mode_traitement Nom du mode de traitement
   *
   * @return null|string
   */
  public function createModeTraitement($name_mode_traitement) {
    $driver = $this->driver;
    $this->accessControlTab('tab-CChargePriceIndicator');

    $driver->byCss("#tab-CChargePriceIndicator > button.new")->click();
    $driver->changeFrameFocus();

    $code = $driver->byId("edit-cpi_code");
    $code->sendKeys($name_mode_traitement);
    $code->submit();

    // reopen modal
    $driver->byCss('#tab-CChargePriceIndicator button:nth-child(1).edit')->click();
    $driver->changeFrameFocus();
    return $driver->byId("edit-cpi_code")->getAttribute('value');
  }
}