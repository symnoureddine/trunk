<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Tests\Unit;

use Ox\Mediboard\Patients\CCorrespondantPatient;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CCorrespondantPatientTest extends UnitTestMediboard {
  /**
   * @param string $cp CP to test
   *
   * @config dPpatients INSEE france 1
   * @config dPpatients INSEE suisse 1
   * @config dPpatients INSEE allemagne 1
   * @config dPpatients INSEE espagne 1
   * @config dPpatients INSEE portugal 1
   * @config dPpatients INSEE gb 1
   *
   * @dataProvider cpProvider
   */
  public function testCPSize($cp) {
    $cp_fields = ['cp'];

    $correspondant = new CCorrespondantPatient();
    foreach ($cp_fields as $_cp) {
      $correspondant->{$_cp} = $cp;
    }

    $correspondant->repair();

    foreach ($cp_fields as $_cp) {
      $this->assertEquals($cp,
        $correspondant->{$_cp});
    }
  }

  public function cpProvider() {
    return array(
      ["3750-012"], ["12"], ["17000"], ["6534887"]
    );
  }
}