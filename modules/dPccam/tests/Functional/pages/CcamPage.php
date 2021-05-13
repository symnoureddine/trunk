<?php
/**
 * @package Mediboard\Ccam\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


use Facebook\WebDriver\WebDriverBy;
use Ox\Tests\HomePage;

/**
 * CCAM page representation
 */
class CcamPage extends HomePage {

  protected $module_name = "dPccam";
  protected $tab_name = 'configure';

  /**
   * Import the given database
   *
   * @param string $basename The name of the database to import
   *
   * @return void
   */
  public function importDatabase($basename) {
    $driver = $this->driver;

    $driver->byId("update_$basename", 30)->click();
  }

  /**
   * Switch the configure tab to NGAP
   *
   * @return void
   */
  public function goToNGAPConfig() {
    $this->accessControlTab('NGAP');
  }

  /**
   * Get the errors and warnings in the results of the import of the given database
   *
   * @param string $basename Database name
   *
   * @return array
   */
  public function getUpdateErrors($basename) {
    $errors = $this->driver->findElements(WebDriverBy::cssSelector("td#$basename div.error"));
    $res = array_merge($errors, $this->driver->findElements(WebDriverBy::cssSelector(("td#$basename div.warning"))));
    $this->driver->waitForAjax($basename, 100);

    return $res;
  }
}