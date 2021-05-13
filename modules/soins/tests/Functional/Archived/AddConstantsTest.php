<?php
/**
 * @package Mediboard\Soins\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * AddConstantsTest
 *
 * @description Test Add a constant of weight and size. Checks the value of the BMI and the value of the weight in grams
 * @screen      SejourPage
 */
class AddConstantsTest extends SeleniumTestMediboard {
  /** @var SejourPage $page */
  public $page = null;
  public $weight = 74.6;
  public $size = 179.4;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new SejourPage($this);
//    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
//    $this->importObject("soins/tests/Functional/data/sejour_test.xml");
//  }


  /**
   * Add two constants and checking of the value of BMI and weight in grams.
   */
  public function testAddConstantsOk() {
    $this->page->addWeightAndSize($this->weight, $this->size);

    $this->assertEquals("Constante médicale mise à jour", $this->page->getSystemMessage());
    $this->assertEquals($this->page->calculateWeightInGrams($this->weight), $this->page->getWeightInGrams());
    $this->assertEquals($this->page->calculateBMI($this->weight, $this->size), $this->page->getBMIValue());
  }

  /**
   * Add two constants and checking of the value of BMI and weight in grams.
   *
   * @config [CConfiguration] dPpatients CConstantesMedicales unite_ta mmHg
   */
  public function testEarlyWarningSigns() {
    $constants = array(
      'frequence_respiratoire' => 22,
      'spo2'                   => 85,
      '_ta_systole'            => 95,
      '_ta_diastole'           => 80,
      'pouls'                  => 105,
      'conscience'             => 2
    );

    $this->page->addConstants($constants);

    $this->assertContains("Constante médicale mise à jour", $this->page->getSystemMessage());
    $this->assertEquals(9, $this->page->getEarlyWarningSigns());


    $constants = array(
      'frequence_respiratoire' => 30,
      'spo2'                   => 95,
      '_ta_systole'            => 85,
      '_ta_diastole'           => 80,
      'pouls'                  => 150,
      'conscience'             => 3
    );

    $this->page->addConstants($constants);

    $this->assertContains("Constante médicale mise à jour", $this->page->getSystemMessage());
    $this->assertEquals(11, $this->page->getEarlyWarningSigns());
  }

  /**
   * Test les seuils d'alerte des constantes
   *
   * @pref constants_table_orientation vertical
   */
  public function testConstantWarningLevels() {
    $this->page = new ConstantConfigsPage($this);
    $this->page->setWarningLevel();
    $this->assertContains('Configuration mise à jour', $this->page->getSystemMessage());

    $this->page = new SejourPage($this);
    $this->page->addConstants(array('pouls' => 90));
    $this->assertFalse($this->page->constantCheckAlert('Pouls', 1));
    $this->page->addConstants(array('pouls' => 40));
    $this->assertTrue($this->page->constantCheckAlert('Pouls', 1));
    $this->page->addConstants(array('pouls' => 140));
    $this->assertTrue($this->page->constantCheckAlert('Pouls', 1));
  }
}