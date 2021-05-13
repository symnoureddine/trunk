<?php
/**
 * @package Mediboard\Astreintes\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;

/**
 * Astreintes page representation
 */
class AstreintesPage extends HomePage {
  protected $module_name = "astreintes";
  protected $tab_name = "vw_astreinte_cal";

  /**
   * Créer une plage d'astreinte ponctuelle
   *
   * @param string $user_name        User name
   * @param string $choose_astreinte Choose astreinte
   * @param string $number           Number range to create
   *
   * @return void
   */
  public function createAstreinte($user_name, $choose_astreinte = "ponc", $number) {
    $driver = $this->driver;
    $form = "editplage";

    $driver->byCss("button.new")->click();

    if ($choose_astreinte != "ponc") {
      $driver->byId($form."_choose_astreinte_reguliere")->click();
    }

    $driver->selectOptionByText($form."_user_id", $user_name);
    $driver->byId($form."_libelle")->sendKeys("Entretien");
    $driver->selectOptionByText($form."_type", "Technique");

    // select start date
    $driver->byId($form."_start_da")->click();
    $dt_before = CMbDT::dateTime('-3 hour');
    $this->selectDate(CMbDT::format($dt_before, '%d'), CMbDT::format($dt_before, '%H'));

    // select end date
    $driver->byId($form."_end_da")->click();
    $dt_after = CMbDT::dateTime('+3 hour');
    $this->selectDate(CMbDT::format($dt_after, '%d'), CMbDT::format($dt_after, '%H'));

    if ($choose_astreinte != "ponc") {
      $driver->byId($form."_phone_astreinte")->clear();
      $driver->byXPath("//button[contains(@onclick, 'setPhone')]")->click();

      $repeat_week = $driver->byId($form."__repeat_week");
      $repeat_week->clear();
      $repeat_week->sendKeys($number);
    }

    $driver->byCss("td.button button.submit")->click();
  }

  /**
   * Check if the astreinte is created
   *
   * @param string $user_name        User name
   * @param string $choose_astreinte Choose astreinte
   * @param string $number           Number range to create
   *
   * @return int
   */
  public function checkIfAstreinteCreated($user_name, $choose_astreinte = "ponc", $number) {
    $driver = $this->driver;

    if ($choose_astreinte == "ponc") {
      $elements = $driver->findElements(WebDriverBy::xpath("//span[contains(text(), '$user_name')]"));
      $result = count($elements);
    }
    else {
      $elements = $driver->findElements(WebDriverBy::xpath("//span[contains(text(), '$user_name')]"));
      $result = count($elements);

      // Change date
      for ($i = 1; $i < $number; $i++) {
        $driver->byCss("a.button.right")->click();
        $elements = $driver->findElements(WebDriverBy::xpath("//span[contains(text(), '$user_name')]"));
        $result   += count($elements);
      }
    }

    return $result;
  }

  /**
   * Check if the astreinte is created
   *
   * @return bool
   */
  public function checkListPersonnelAstreinte() {
    $driver = $this->driver;
    $driver->byCss("div.minitoolbar a.singleclick")->click();
    $driver->changeFrameFocus();

    $driver->byCss("div.title span.left")->isDisplayed();

    $elements = $driver->findElements(WebDriverBy::xpath("//span[@class='mediuser ']"));
    $result   = count($elements);

    return $result == 2;
  }
}
