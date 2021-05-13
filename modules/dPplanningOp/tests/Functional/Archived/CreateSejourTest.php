<?php

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateDHETest
 *
 * @description Test creation of a new "DHE" with a protocol
 * @screen      PlanifSejourProtocoleDHEPage, PlanifSejourIntervPage
 *
 * @package    Mediboard
 * @subpackage Tests
 * @author     SARL OpenXtrem <dev@openxtrem.com>
 * @license    GNU General Public License, see http://www.gnu.org/licenses/gpl.html
 * @link       http://www.mediboard.org
 */
class CreateSejourTest extends SeleniumTestMediboard {
  /** @var PlanifSejourSejourPage */
  public $planifSejourPage;
  public $patientName = "PATIENTLASTNAME";
  public $patientFirtName = "Patientfirstname";
  public $chirName = "CHIR Test";
  public $libelle = "libelle";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->planifSejourPage = new PlanifSejourSejourPage($this);
//    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
//  }

  /**
   * Création d'un séjour
   */
  public function testCreateSejourOk() {
    $page = $this->planifSejourPage;
    $page->createSejour($this->patientName, $this->patientFirtName, $this->chirName, $this->libelle);
    $this->assertContains("Séjour créé", $page->getSystemMessage());
  }
}