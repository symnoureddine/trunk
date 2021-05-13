<?php
/**
 * @package Mediboard\Cim10\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Mediboard\System\Tests\Functional\Pages\SystemPage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * CimTest
 *
 * @description Test add a CIM code to favorites
 * @screen      CimPage
 */
class CimTest extends SeleniumTestMediboard {

  public $cimCode = "J011";

  /**
   * Teste sur la mise à jour des base Cim
   */
  public function testUpdateCim() {
    $system = new SystemPage($this);

    if ($system->isDataSourceUpdated('dPcim10')) {
      return;
    }

    $system->goToConfigureView('dPcim10');

    $page = new CimPage($this);
    $page->goToImportView();

    $page->importDatabase('oms');
    $errors = $page->getUpdateErrors('oms');
    $this->assertEmpty($errors);

    $page->importDatabase('oms_update');
    $errors = $page->getUpdateErrors('oms_update');
    $this->assertEmpty($errors);

    $page->importDatabase('atih');
    $errors = $page->getUpdateErrors('atih');
    $this->assertEmpty($errors);

    $page->importDatabase('gm');
    $errors = $page->getUpdateErrors('gm');
    $this->assertEmpty($errors);
  }
}