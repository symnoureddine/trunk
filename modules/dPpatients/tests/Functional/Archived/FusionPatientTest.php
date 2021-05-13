<?php
/**
 * @package Mediboard\Patients\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * FusionPatientTest
 *
 * @description Test fusion of two patients
 * @screen      DossierPatientPage
 */
class FusionPatientTest extends SeleniumTestMediboard {

  /**
   * Fusion de deux patients par la vue du dossier patient
   */
  public function testFusionPatientWithPatientSearchOk() {
    $dPpage = new DossierPatientPage($this);

    // Patients import
    $this->importObject("system/tests/Functional/data/patients_merge.xml");

    $dPpage->searchPatientByFirstName("Patientfirstname");
    $dPpage->selectMergePatients();
    $dPpage->doMerge();
    $dPpage->searchPatientByName("MERGE");
    $this->assertFalse($dPpage->hasExactMatches());
  }
}