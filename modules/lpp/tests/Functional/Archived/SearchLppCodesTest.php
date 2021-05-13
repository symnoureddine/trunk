<?php
/**
 * @package Mediboard\Lpp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * Test search of LPP code
 *
 * @description Test search of LPP code
 *
 * @screen LppPage
 */
class SearchLppCodesTest extends SeleniumTestMediboard {

  /** @var LppPage The page */
  public $page;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//
//    $this->page = new LppPage($this);
//  }

  /**
   * Test la recherche d'un code LPP
   */
  public function testSearchByCode() {
    $this->page->searchByCode();
    $this->assertEmpty($this->page->getEmptyResult());
  }

  /**
   * Test la recherche d'un code LPP par texte
   */
  public function testSearchByText() {
    $this->page->searchByText();
    $this->assertEmpty($this->page->getEmptyResult());
  }

  /**
   * Test la recherche d'un code LPP par chapitres
   *
   * @return void
   */
  public function testSearchByChapters() {
    $this->page->searchByChapters();
    $this->assertEmpty($this->page->getEmptyResult());
  }
}
