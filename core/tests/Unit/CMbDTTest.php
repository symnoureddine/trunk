<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


namespace Ox\Core\Tests\Unit;

use DateTime;
use Exception;
use Ox\Core\CMbDT;
use Ox\Tests\UnitTestMediboard;

/**
 * Class CMbDTTest
 */
class CMbDTTest extends UnitTestMediboard {

  /**
   * @throws Exception
   */
  public function testAchievedDurationsDT() {
    $from   = (new DateTime("10-10-2019"))->format("Y-m-d");
    $to     = (new DateTime("20-07-2021"))->format("Y-m-d");
    $result = CMbDT::achievedDurationsDT($from, $to);

    $expected = ["year" => 1, "month" => 21, "week" => 91, "day" => 648, "locale" => "21 months"];

    $this->assertEquals($expected, $result);
  }
}
