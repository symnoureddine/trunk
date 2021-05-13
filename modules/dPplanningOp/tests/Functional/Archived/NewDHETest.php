<?php 
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateDHETest
 *
 * @description Test the creation of sejours, operations, and consultations with the new DHE view
 * @screen      PlanifSejourNewDHEPage
 */
class NewDHETest extends SeleniumTestMediboard {
  /** @var PlanifSejourNewDHEPage */
  public $page;
  public $patientName = "PATIENTLASTNAME";
  public $patientFirtName = "Patientfirstname";
  public $chirName = "CHIR Test";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new PlanifSejourNewDHEPage($this);
//    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
//  }

  /**
   * Test the creation of a sejour using the new DHE
   *
   * @config [CConfiguration] dPplanningOp CSejour new_dhe 1
   */
  public function testCreateSejour() {
    $this->page->setSejour($this->patientName, $this->chirName);
    $msg = $this->page->create();
    $this->assertContains("Séjour créé", $msg);
  }

  /**
   * Test the creation of a sejour with a protocol in the new DHE
   *
   * @config [CConfiguration] dPplanningOp CSejour new_dhe 1
   */
  public function testCreateSejourWithProtocol() {
    $pageProtocol = new PlanifSejourProtocoleDHEPage($this);
    $pageProtocol->createProtocoleDHE('testCreateSejourWithProtocol', false);
    $this->page->switchTab('vw_dhe');
    $this->page->setSejourWithProtocol($this->patientName, $this->chirName, 'testCreateSejourWithProtocol');
    $msg = $this->page->create();
    $this->assertContains("Séjour créé", $msg);
  }

  /**
   * Test the creation of a sejour with a consultation
   *
   * @config [CConfiguration] dPplanningOp CSejour new_dhe 1
   */
  public function testCreateSejourConsultation() {
    $this->page->setSejour($this->patientName, $this->chirName);
    $this->page->setConsultation();
    $msg = $this->page->create();
    $this->assertContains("Séjour créé", $msg);
    $this->assertContains("Consultation créé", $msg);
  }

  /**
   * Test the creation of a sejour with an operation
   *
   * @config [CConfiguration] dPplanningOp CSejour new_dhe 1
   */
  public function testCreateSejourOperation() {
    $this->page->setSejour($this->patientName, $this->chirName);
    $this->page->setOperation();
    $msg = $this->page->create();
    $this->assertContains("Séjour créé", $msg);
    $this->assertContains("Intervention créé", $msg);
  }

  /**
   * Test the creation of a sejour with an operation, by using a protocol
   *
   * @config [CConfiguration] dPplanningOp CSejour new_dhe 1
   */
  public function testCreateSejourOperationWithProtocol() {
    $pageProtocol = new PlanifSejourProtocoleDHEPage($this);
    $pageProtocol->createProtocoleDHE('testCreateSejourWithProtocol');
    $this->page->switchTab('vw_dhe');
    $this->page->setOperationWithProtocol($this->patientName, $this->chirName, 'testCreateSejourWithProtocol');
    $msg = $this->page->create();
    $this->assertContains("Séjour créé", $msg);
    $this->assertContains("Intervention créé", $msg);
  }
}