<?php
/**
 * @package Mediboard\Urgences\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Circumstance page representation
 */
class CircumstancePage extends HomePage {
  protected $module_name = "dPurgences";
  protected $tab_name = "vw_circonstances";

  /**
   * Create a circumstance
   *
   * @param string $code        Circumstance code
   * @param string $libelle     Circumstance libelle
   * @param string $commentaire Circumstance comment
   *
   * @return void
   */
  public function createCircumstance($code, $libelle, $commentaire) {
    $driver = $this->driver;
    $name_form = "editCirc";

    $driver->byCss("div.content button.new")->click();

    $driver->byId($name_form."_code")->sendKeys($code);
    $driver->byId($name_form."_libelle")->sendKeys($libelle);
    $driver->byId($name_form."_commentaire")->sendKeys($commentaire);

    $driver->byCss("button.submit")->click();
  }

  /**
   * Put the circumstance in active or inactive
   *
   * @return void
   */
  public function putCircumstanceActiveOrInactive() {
    $driver = $this->driver;
    $value = $driver->byXPath("//input[@type='radio' and @checked]//following-sibling::label")->getText();

    if ($value == 'Oui') {
      $driver->byXPath("//label[contains(text(),'Non')]")->click();
    }
    else {
      $driver->byXPath("//label[contains(text(),'Oui')]")->click();
    }
  }
}
