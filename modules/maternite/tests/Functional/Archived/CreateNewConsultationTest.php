<?php
/**
 * @package Mediboard\Maternite\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateNewConsultationTest
 *
 * @description Test create a new consultation
 * @screen      DashboardPage
 */
class CreateNewConsultationTest extends SeleniumTestMediboard {
  /** @var DashboardPage */
  public $dashboardPage;
  public $patientName = "MOTHERLASTNAME";
  public $patientFirtName = "Motherfirstname";
  public $chir = "CHIR Test";
  public $date = null;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->date = CMbDT::date();
//    $this->dashboardPage = new DashboardPage($this);
//    $this->importObject("maternite/tests/Functional/data/patiente_test.xml");
//  }

  /**
   * Create a new consultation
   */
  public function testCreateNewConsultationOk() {
    $page = $this->dashboardPage;
    $page->switchModule("dPcabinet");
    $page->openModalAndcreatePlageConsultation($this->chir, $this->date);
    $this->assertEquals("Plage créée", $page->getSystemMessage());

    $page->switchModule("maternite");
    $page->createNewConsultation($this->chir, $this->patientName);
    $this->assertEquals("Consultation créée", $page->getSystemMessage());
  }
}