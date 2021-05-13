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
 * UploadTest
 *
 * @description Test file upload
 * @screen      DossierPatientPage
 */
class UploadTest extends SeleniumTestMediboard {

  /** @var $dpPage DossierPatientPage */
  public $dpPage = null;

  /**
   * Téléversement de fichier dans le dossier patient
   */
  public function testUploadFilePatientOk() {
    $this->markTestSkipped('Not tested on gitlab-ci');
    $page = new DossierPatientPage($this);

    // Patients import
    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
    
    $file_name = "file.pdf";
    $file_uploaded = $page->testUploadFilePatient($file_name);

    $this->assertEquals($file_name, $file_uploaded);
  }
}