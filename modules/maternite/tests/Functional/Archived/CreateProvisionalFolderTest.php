<?php
/**
 * @package Mediboard\Maternite\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateProvisionalFolderTest
 *
 * @description Test sur la création d'un dossier provisoire
 * @screen      ValidationStaysPage
 */
class CreateProvisionalFolderTest extends SeleniumTestMediboard {
  /** @var ValidationStaysPage */
  public $validationStaysPage;
  public $today;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->validationStaysPage = new ValidationStaysPage($this);
//    $this->importObject("maternite/tests/Functional/data/sejour_grossesse.xml");
//  }

  /**
   * Créer un dossier provisoire
   *
   * @config [CConfiguration] maternite CGrossesse manage_provisoire 1
   */
  public function testCreateProvisionalFolderOk() {
    $this->today = CMbDT::format(CMbDT::date(), CAppUI::conf("date"));
    $page        = $this->validationStaysPage;

    $page->createProvisionalFolder();

    $this->assertEquals($this->today, $page->getBirthDate());
    $this->assertEquals("Enf. MOTHERLASTNAME Provisoire", $page->getProvisionalFolderName());
  }
}