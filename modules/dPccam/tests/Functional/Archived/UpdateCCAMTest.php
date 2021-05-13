<?php
/**
 * @package Mediboard\Ccam\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Mediboard\System\Tests\Functional\Pages\SystemPage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * @description Update the CCAM datasource and test if the import if successful
 * @screen      CcamPage
 */
class UpdateCCAMTest extends SeleniumTestMediboard {

  /** @var CcamPage $ccam_page */
  public $ccam_page;

  /** @var SystemPage $system_page */
  public $system_page;

  /*
  public static $browsers = array(
    "windows_chrome" => array(
      'name' => 'Windows Chrome',
      'type' => 'chrome',
      'desiredCapabilities' => array(
        'unexpectedAlertBehaviour' => 'ignore',
        'platform' => 'windows',
        'platformName' => 'windows',
      )
    )
  );
  */

  /**
   * Update the datasource CCAM if necessary, and check if the import goes well
   */
  public function testUpdateCCAM() {
    $system = new SystemPage($this);

    if ($system->isDataSourceUpdated('dPccam')) {
      return;
    }

    $page = new CcamPage($this);

    $page->importDatabase('ccam');
    $errors = $page->getUpdateErrors('ccam');
    $this->assertEmpty($errors);

    $page->importDatabase('forfaits');
    $errors = $page->getUpdateErrors('forfaits');
    $this->assertEmpty($errors);

    $page->importDatabase('ccam_convergence');
    $errors = $page->getUpdateErrors('ccam_convergence');
    $this->assertEmpty($errors);

    $page->goToNGAPConfig();

    $page->importDatabase('ngap');
    $errors = $page->getUpdateErrors('ngap');
    $this->assertEmpty($errors);
  }
}