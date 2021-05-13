<?php
/**
 * @package Mediboard\Lpp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * The Page for the LPP module
 */
class LppPage extends HomePage{
  /** @var string  */
  protected $module_name = 'lpp';
  
  /** @var string */
  protected $tab_name = 'vw_search';

  /**
   * Search LPP codes by code
   *
   * @return void
   */
  public function searchByCode() {
    $this->driver->byId('searchLPPCode_code')->sendKeys('223');
    $this->search();
  }

  /**
   * Search LPP codes by text
   *
   * @return null
   */
  public function searchByText() {
    $this->driver->byId('searchLPPCode_text')->sendKeys('orthese');
    $this->search();
  }

  /**
   * Launch the LPP code search
   *
   * @return void
   */
  public function search() {
    $this->driver->byId('search_codes')->click();
  }

  /**
   * Search PP codes by chapters
   *
   * @return void
   */
  public function searchByChapters() {
    $this->driver->selectOptionByValue('searchLPPCode_chapter_1', '02');
    $this->driver->byId('searchLPPCode_chapter_2');
    $this->driver->selectOptionByValue('searchLPPCode_chapter_2', '025');
    $this->driver->byId('searchLPPCode_chapter_3');
    $this->driver->selectOptionByValue('searchLPPCode_chapter_3', '0251');
    $this->search();
  }

  /**
   * Return and empty array if there are no results
   *
   * @return array
   */
  public function getEmptyResult() {
    $this->driver->getElementsByCss('div#results td.empty');
  }
}
