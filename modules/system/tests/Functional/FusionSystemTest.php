<?php
/**
 * @package Mediboard\System\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional;

use DossierPatientPage;
use Ox\Mediboard\System\Tests\Functional\Pages\SystemPage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * FusionSystemTest
 *
 * @description Test fusion of objects
 * @screen      SystemPage
 */
class FusionSystemTest extends SeleniumTestMediboard {

  /**
   * Fusion de deux patients et vérifie que le patient fusionné est bien supprimé
   */
//  public function testFusionPatientWithObjectMergerOk() {
//    $this->markTestSkipped('Not tested on gitlab-ci');
//    $systemPage = new SystemPage($this, false);
//    $this->url("$this->base_url/?login=$systemPage->credentials");
//    // Patients import
//    $this->importObject("system/tests/Functional/data/patients_merge.xml");
//    // Custom url in order to avoid loading all mediboard classes
//    $this->url("$this->base_url/index.php?m=system&tab=object_merger&readonly_class=1&objects_class=CPatient");
//    $systemPage->compareObjects("BASE", "MERGE");
//    $systemPage->doMerge();
//    $systemPage->switchModule("dPpatients");
//    $dPpage = new DossierPatientPage($this, false);
//    $dPpage->searchPatientByName("BASE");
//    $this->byCss("#list_patients > tbody > .patientFile a")->click();
//    sleep(1);
//
//    $this->assertEquals("BASE", $dPpage->getPatientName());
//    $dPpage->searchPatientByName("MERGE");
//    $this->assertFalse($dPpage->hasExactMatches());
//  }

  /**
   * Fusion de deux séjours pour un même patient et vérifie que le patient ne possède bien qu'un séjour.
   */
//  public function testFusionSejourOk() {
//    $this->markTestSkipped('Not tested on gitlab-ci');
//    $systemPage = new SystemPage($this, false);
//    $this->url("$this->base_url/?login=$systemPage->credentials");
//    // Custom url in order to avoid loading all mediboard classes
//    $this->url("$this->base_url/index.php?m=system&tab=object_merger&readonly_class=1&objects_class=CSejour");
//    // Patient and sejour import
//    $this->importObject("system/tests/Functional/data/sejour_merge.xml");
//    $systemPage->compareObjects("FUSIONSEJOUR");
//    $systemPage->doMerge();
//    $systemPage->switchModule("dPpatients");
//
//    $dPpage = new DossierPatientPage($this, false);
//    $dPpage->searchPatientByName("FUSIONSEJOUR");
//    // Check if patient has only one sejour
//    $count_sjour = $dPpage->getSejourCount();
//    $this->assertEquals(1, $count_sjour);
//    $planifPage = $dPpage->editSejour();
//
//    // Check if the right sejour has been merged
//    $this->assertEquals("Base sejour", $planifPage->getSejourRques());
//  }

}
