<?php
/**
 * @package Mediboard\Maternite\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\HomePage;

/**
 * ValidationStaysPage page representation
 */
class ValidationStaysPage extends HomePage {

  protected $module_name = "maternite";
  protected $tab_name = "vw_admissions";

  /**
   * Create a new provisional folder
   *
   * @return void
   */
  public function createProvisionalFolder() {
    $driver = $this->driver;

    // Rafraichit la page
    $driver->navigate()->refresh();

    $driver->byCss("table#admissions th.title a:nth-child(1)")->click();

    $driver->byCss("td.narrow button.add")->click();
    $driver->changeFrameFocus();
    $driver->byId("labelFor_newNaissance_sexe_f")->click();

    $driver->byId("newNaissance__only_pediatres")->click();
    $driver->byId("newNaissance__prat_autocomplete")->sendKeys("CHIR Test");
    $driver->byXPath("//div[@class='autocomplete']//span[contains(text(),'CHIR')]")->click();

    $driver->byCss("button.singleclick")->click();

    if ($driver->getBrowserName() == "internet explorer") {
      sleep(1);
    }
    $driver->navigate()->refresh();
  }

  /**
   * Returns the date of the birth
   *
   * @return string the date
   */
  public function getBirthDate() {
    $birthDate = $this->driver->byCss("table#admissions tbody.hoverable td:nth-child(5) span")->getText();

    return $birthDate;
  }

  /**
   * Returns the value of the provisional folder
   *
   * @return string the value
   */
  public function getProvisionalFolderName() {
    $name = $this->driver->byCss("table#admissions td:nth-child(8) span")->getText();

    return $name;
  }
}