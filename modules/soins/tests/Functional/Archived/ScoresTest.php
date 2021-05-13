<?php 
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * Class ScoresTest
 *
 * @description Test the creation of the Chung and IGS scores
 * @screen      SejourPage
 */
class ScoresTest extends SeleniumTestMediboard {
  /** @var SejourPage $page */
  public $page = null;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new SejourPage($this);
//  }

  /**
   * Tests the value of the Chung score based on the constants preop and perop
   */
  public function testChungScore() {
    $this->importObject("soins/tests/Functional/data/score_chung.xml");
    $this->assertEquals(6, $this->page->createChungScore());
    $this->assertContains('Score de Chung créé', $this->page->getSystemMessage());
  }

  /**
   * Test the value of the IGS score based on the constants
   */
  public function testIgsScore() {
    $this->importObject("soins/tests/Functional/data/score_igs.xml");
    $this->assertEquals(54, $this->page->createIgsScore());
    $this->assertContains('Score IGS créé', $this->page->getSystemMessage());
  }

  /**
   * Test creation honos in sejour
   *
   * @config [CConfiguration] anq Category see_honos 1
   */
  public function testHonosScore() {
    $this->importObject("soins/tests/Functional/data/sejour_honos.xml");
    $this->page->createAnqScore(CAnqHonos::$_fields_age["adult"], "CAnqHonos");
    $this->assertContains('Honos créé', $this->page->getSystemMessage());
  }

  /**
   * Test creation BSCL in sejour
   *
   * @config [CConfiguration] anq Category see_bscl 1
   */
  public function testBSCLScore() {
    $this->importObject("soins/tests/Functional/data/sejour_honos.xml");
    $this->page->createAnqScore(CAnqBSCL::$_fields_age["all"], "CAnqBSCL");
    $this->assertContains('BSCL créé', $this->page->getSystemMessage());
  }

  /**
   * Test creation CIRS in sejour
   *
   * @config [CConfiguration] anq Category see_cirs 1
   */
  public function testCIRSScore() {
    $this->importObject("soins/tests/Functional/data/sejour_honos.xml");
    $this->page->createAnqScore(CAnqCIRS::$_fields_age["all"], "CAnqCIRS");
    $this->assertContains('CIRS créé', $this->page->getSystemMessage());
  }

  /**
   * Test creation MIF in sejour
   *
   * @config [CConfiguration] anq Category see_mif 1
   */
  public function testMIFScore() {
    $this->importObject("soins/tests/Functional/data/sejour_honos.xml");
    $this->page->createAnqScore(CAnqMIF::$_fields_age["all"], "CAnqMIF");
    $this->assertContains('MIF créé', $this->page->getSystemMessage());
  }

  /**
   * Test creation EFM in sejour
   *
   * @config [CConfiguration] anq Category see_efm 1
   */
  public function testEFMScore() {
    $this->importObject("soins/tests/Functional/data/sejour_honos.xml");
    $this->page->createAnqScore(CAnqEFM::$_fields_age["all"], "CAnqEFM");
    $this->assertContains('Mesure de contrainte créée', $this->page->getSystemMessage());
  }

  /**
   * Test check honos not empty for patient during 72 first hour of sejour
   *
   * @config [CConfiguration] anq Category see_honos 1
   */
  public function testPresenceAlerteHonos() {
    $this->importObject("soins/tests/Functional/data/sejour_honos.xml");
    $this->page->openTabHonos("CAnqHonos");
    $this->assertTrue($this->page->checkAlertHonosEntree());
    $this->page->createAnqScore(CAnqHonos::$_fields_age["adult"], "CAnqHonos", "entree", false);
    $this->assertFalse($this->page->checkAlertHonosEntree());
  }
}