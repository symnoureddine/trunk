<?php
/**
 * @package Mediboard\Patients\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * AntecedentsAndAntecedentsAbsentTest
 *
 * @description Test creation antecedents and antecedents absent. Check theirs informations and patient banner.
 * @screen      DossierPatientPage
 */
class AntecedentsAndAntecedentsAbsentTest extends SeleniumTestMediboard {

  /** @var DossierPatientPage $page */
  public $page = null;

  public $patientLastname = "WAYNE";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new DossierPatientPage($this);
//    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");
//  }

  /**
   * Créé des antécedents et antécédents absent et vérifications des informations et du bandeau patient.
   */
  public function testAntecedentsAndAntecedentsAbsentWithCheckingOk() {
    $page = $this->page;
    $page->openModalDossierSoins($this->patientLastname);
    $page->createAntecedentsAndAntecedentsAbsent();
    $this->assertEquals($page->getCountAtcd(1), $page->checkAntecedentNumber(1));
    $this->assertEquals($page->getCountAtcd(2), $page->checkAntecedentNumber(2));
    $this->assertTrue($page->checkFlagsAtcdLevel());
    $this->assertEquals(1, $page->checkAllergieFlag());
  }
}
