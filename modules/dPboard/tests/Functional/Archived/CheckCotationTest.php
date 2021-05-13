<?php
/**
 * @package Mediboard\Board\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CheckCotationTest
 *
 * @description Tests de la vue Saisie des cotations
 * @screen      CheckCotationPage
 */
class CheckCotationTest extends SeleniumTestMediboard {
  /** @var CheckCotationPage */
  public $page;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//
//    $this->page = new CheckCotationPage($this);
//  }

  /**
   * Import the given data file
   *
   * @param string $test The test data to import
   *
   * @return void
   */
  protected function importData($test) {
    $this->importObject("dPboard/tests/Functional/data/$test.xml");
  }

  /**
   * Teste la détection des interventions non cotées pour deux praticiens
   *
   * @return void
   */
  public function testCheckCotation() {
    $this->importData('check_cotation');

    $this->page->checkCotationFor('Cotationc');
    $this->assertEquals(2, $this->page->countInterventions());

    $this->page->checkCotationFor('Cotationa');
    $this->assertEquals(2, $this->page->countInterventions());
  }

  /**
   * Teste la vérification de la validation des codages
   *
   * @return void
   */
  public function testCheckValidation() {
    $this->importData('check_validation');

    $this->page->checkCotationFor('Cotationc');

    $this->page->checkValidationFor('locked');
    $this->assertEquals(1, $this->page->countInterventions());

    $this->page->checkValidationFor('locked_by_chir');
    $this->assertEquals(1, $this->page->countInterventions());

    $this->page->checkValidationFor('unlocked');
    $this->assertEquals(1, $this->page->countInterventions());
  }

  /**
   * Teste le codage en masse des interventions
   *
   * @config [CConfiguration] dPccam codage mass_coding 1
   *
   * @return void
   */
  public function testMassCoding() {
    $this->importData('mass_coding');

    $this->page->checkCotationFor('Cotationc');

    $this->page->massCoding('MJCA012');
    $this->assertEquals('Le codage a été appliqué avec succès à 2 interventions', $this->page->getSystemMessage());
  }

  /**
   * Teste le codage en masse des interventions
   *
   * @config [CConfiguration] dPccam codage mass_coding 1
   *
   * @return void
   */
  public function testMassCodingSeances() {
    $this->importData('mass_coding_seances');

    $this->page->checkCotationFor('Cotationc');

    $this->page->massCoding('EBLA001', 'CSejour-seance');
    $this->assertEquals('Le codage a été appliqué avec succès à 2 interventions', $this->page->getSystemMessage());
  }
}
