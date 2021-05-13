<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional;

use DossierPatientPage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * PrintTest
 *
 * @description Test document printing
 * @screen      DossierPatientPage
 */
class PrintTest extends SeleniumTestMediboard {


  /** @var $dpPage DossierPatientPage */
  public $dpPage = null;

  /**
   * Impression de document dans le dossier patient
   */
  public function testPrintDocPatientOk() {
    $this->markTestSkipped('Unable to check tearDown object deletion status because of print modal');
    $page = new DossierPatientPage($this);

    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
    $this->importObject("dPcompteRendu/tests/Functional/data/modele_patient_test.xml");

    $this->assertTrue($page->testPrintDocPatient());
  }
}