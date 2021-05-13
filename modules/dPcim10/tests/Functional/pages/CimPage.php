<?php
/**
 * @package Mediboard\Cim10\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Ox\Tests\HomePage;

/**
 * CIM page representation
 */
class CimPage extends HomePage {

  protected $module_name = "dPcim10";
  protected $tab_name = "cim";

  /**
   * Switch to the configure view
   *
   * @return void
   */
  public function goToImportView() {
    $this->switchTab('configure');
  }

  /**
   * Import the given database
   *
   * @param string $basename The name of the database
   *
   * @return void
   */
  public function importDatabase($basename) {
    $this->driver->byId("import_cim10_$basename", 30)->click();
  }

  /**
   * Get the errors and warnings in the results of the import of the given database
   *
   * @param string $basename Database name
   *
   * @return array
   */
  public function getUpdateErrors($basename) {
    $errors = $this->driver->findElements(WebDriverBy::cssSelector("td#cim10_$basename div.error"));
    $res = array_merge($errors, $this->driver->findElements(WebDriverBy::cssSelector(("td#cim10_$basename div.warning"))));
    $this->driver->waitForAjax("cim10_$basename", 100);

    return $res;
  }

  /**
   * Show the categories list of the given chapter
   *
   * @param string $chapter_code The chapter code
   *
   * @return void
   */
  public function selectChapter($chapter_code) {
    $this->driver->byCss("li#chapter-{$chapter_code} div.chapter-container span.cim10-code")->click();
  }

  /**
   * Show the given code
   *
   * @param string $code A category or code
   *
   * @return void
   */
  public function showCode($code) {
    $this->driver->byXPath("//div[@id='cim10_details']//a[@class='cim10-code' and contains(@onclick, '{$code}')]")->click();
  }

  /**
   * Return the code shown
   *
   * @return string
   */
  public function getSelectedCode() {
    return $this->driver->byCss('div#cim10_details h2.code-title span.cim10-code')->getText();
  }
  
  /**
   * Add a CIM10 code to the favorites
   *
   * @param string $code Code CIM
   *
   * @return void
   */
  public function addToFavoris($code) {
    $action = $this->driver->action();
    $action->moveToElement($this->driver->byCss("h2.code-title span#editFavoriCIM-{$code}-add i.fa-star"));
    $action->perform();
    $this->driver->byCss("h2.code-title span#editFavoriCIM-{$code}-add span.button")->click();
  }

  /**
   * Perform a search for the given code query
   *
   * @param string $code A full or partial code
   *
   * @return void
   */
  public function searchCIM($code) {
    $this->driver->byCss('div#quick_search_container button.search')->click();
    $this->accessControlTab('search-cim');
    $this->driver->setInputValueById('searchCIM_code', $code);
    $this->driver->byCss('div#search-cim button.search')->click();
    $this->driver->waitForAjax('search-cim-results');
  }

  /**
   * Selct the given code in the search results
   *
   * @param string $code The code to select
   *
   * @return void
   */
  public function selectSearchResult($code) {
    $this->driver->byXPath("//button[contains(@onclick, '$code')]")->click();
    $this->driver->waitForAjax('cim10_details');
  }
}