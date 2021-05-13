<?php
/**
 * @package Mediboard\Files\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Ox\Tests\HomePage;

/**
 * Files category page representation
 */
class FilesCategoryPage extends HomePage {

  protected $module_name = "files";
  protected $tab_name = "vw_categories";


  function createFilesCategory() {
    $driver = $this->driver;
    
    $form = "EditCat";

    // Click on the create button
    $driver->byClassName("new")->click();

    // Wait modal to load
    $driver->byId($form . "_nom")->sendKeys("NomCategorie");

    // Fill the targetted class
    $driver->selectOptionByValue($form . "_class", "CSejour");

    // Click on the create button
    $driver->byClassName("submit")->click();

    // Wait until end of files category creation
    $driver->wait()->until(
      // if button is present, the modal is reloaded
      WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('button.trash'))
    );
  }
}