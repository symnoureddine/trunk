<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbDT;
use Ox\Tests\SeleniumTestMediboard;

/**
 * CreatePlageConsultationtest
 *
 * @description Test creation of a "plage de consultation" ;
 *              The "plage" must be created on an empty week
 * @screen      ConsultationPage
 */
class CreatePlageConsultationTest extends SeleniumTestMediboard {

  /** @var ConsultationsPage $dpPage */
  public $consultationPage = null;

  public $chir_name = "CHIR Test";
  public $date;

  /**
   * Créé une plage de consultation
   */
  public function testCreatePlageConsultation() {
    $this->date = CMbDT::date();
    $this->consultationPage = new ConsultationsPage($this);
    $this->consultationPage->openPlageCreationModal($this->date);
    $this->consultationPage->createPlageConsultation($this->chir_name, $this->date);
    $this->assertEquals("Plage créée", $this->consultationPage->getSystemMessage());
  }

}