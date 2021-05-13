<?php
/**
 * @package Mediboard\System\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional\Pages;


use Ox\Tests\HomePage;

/**
 * System page representation
 */
class SystemPage extends HomePage {

  protected $module_name = "system";
  protected $tab_name = "view_modules";

  /**
   * Check if user modules are up to date
   *
   * @param int $threshold Threshold for the number of update
   *
   * @return bool True if modules are up to date
   */
  public function isUserModulesUpdated($threshold = 0) {
    $driver        = $this->driver;
    $updateButtons = $driver->findElementsByCss('button.upgrade');
    return count($updateButtons) <= $threshold;
  }

  /**
   * Check if core modules are up to date
   *
   * @return bool True if modules are up to date
   */
  public function isCoreModulesUpdated() {
    $driver      = $this->driver;
    $tabsModules = $driver->findElementsById('tabs-modules');
    return count($tabsModules) === 1;
  }

  /**
   * Perform module update by clicking on upgrade button
   *
   * @return void
   */
  public function doUpdate() {
    $driver        = $this->driver;
    $updateButtons = $driver->findElementsByCss('.change');
    if ($updateButtons) {
      $updateButtons[0]->click();
    }
  }

  /**
   * Perform the module update by clicking on the upgrade-all-button
   *
   * @return void
   */
  public function doAllUpdates() {
    $driver = $this->driver;

    $driver->waitUntil(function () use ($driver) {
      $button = $driver->byId('upgrade-all-button');
      if ($button->enabled() || $button->displayed()) {
        $button->click();
        return true;
      }
      return false;
    });

    $this->getSystemMessageElement()->click();
    $driver->refresh();
  }

  /**
   * Click on the "Modules non-installés" tab to display them
   *
   * @return void
   */
  public function displayNotInstalledModules() {
    $this->driver->byCss("#tabs-modules li:nth-child(2) a")->click();
  }

  /**
   * Click on the "Modules installés" tab to display them
   *
   * @return void
   */
  public function displayInstalledModules() {
    $this->driver->byCss("#tabs-modules li:nth-child(1) a")->click();
  }

  /**
   * Check if all the modules are installed
   *
   * @param int $threshold Threshold for the number of installed modules
   *
   * @return bool True if all modules are installed
   */
  public function isModulesInstalled($threshold = 0) {
    $driver = $this->driver;
    $elements  = $driver->findElementsByCss(".new");
    return (count($elements) - $threshold) === 0;
  }

  /**
   * Perform modules installation by clicking on the button
   *
   * @param string|null $module Module name
   *
   * @return void
   */
  public function installModule($module = null) {
    $driver = $this->driver;

    $selector = $module ?
      "//div[@id='notInstalled']//tr[@id='mod_$module']//button" :
      "(//div[@id='notInstalled']//tr[@id]//button)[1]";

    $driver->byXPath($selector)->click();
    $this->getSystemMessageElement()->click();
  }

  /**
   * Set a module active
   *
   * @param string $module Module name
   *
   * @return void
   */
  public function setModuleActive($module) {
    $xpath =
      "//div[@id='installed']//a[@href='?m=$module']/../../..//form[contains(@name,'formActifModule')]//input[@type='checkbox']";
    $this->driver->byXPath($xpath)->click();
  }

  /**
   * Configure a module
   *
   * @param string $module Module name
   */
  public function configureModule($module) {
    $this->driver->byXPath("//div[@id='installed']//a[@href='?m=$module']/../../..//a[@class='button search action']")->click();
  }

  /**
   * Compare two objects on the object merger view
   *
   * @param String $object1 Keyword to find object
   * @param String $object2 Keyword to find object, if null search same object twice
   *
   * @return void
   */
  public function compareObjects($object1, $object2 = null) {
    $driver       = $this->driver;
    $parentWindow = $driver->windowHandles();

    //Select first patient
    $driver->byCss("div.object:nth-child(1) > button")->click();
    $this->objectPopupSelector($object1, $parentWindow);

    //Select second one
    $driver->byCss("div.object:nth-child(2) > button")->click();
    ($object2 === null) ?
      $this->objectPopupSelector($object1, $parentWindow, false) :
      $this->objectPopupSelector($object2, $parentWindow);

    $driver->byCss(".hslip")->click();
  }

  /**
   * Check if the datasources of the given module are up to date
   *
   * @param string $module The module name
   *
   * @return bool
   */
  public function isDataSourceUpdated($module) {
    $driver = $this->driver;

    $elements =
      $driver->findElementsByXpath("//a[@href='?m=$module&a=configure']/../preceding-sibling::td/div[contains(@class, 'warning')]");

    return empty($elements);
  }

  /**
   * Go to the configure view of the given module
   *
   * @param string $module The module name
   *
   * @return void
   */
  public function goToConfigureView($module) {
    $this->driver->byXPath("//a[@href='?m=$module&a=configure']")->click();
  }


}