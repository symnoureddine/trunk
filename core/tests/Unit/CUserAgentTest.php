<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


namespace Ox\Core\Tests\Unit;

use Ox\Mediboard\System\CUserAgent;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CUserAgentTest extends UnitTestMediboard
{


    public function testDetect()
    {
        $ua_string = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.193 Safari/537.36";
        $infos     = CUserAgent::detect($ua_string);

        $this->assertEquals($infos->browser, "Chrome");
        $this->assertEquals($infos->version, "86.0");
        $this->assertEquals($infos->platform, "Win10");
        $this->assertEquals($infos->device_type, "Desktop");

        $this->assertObjectHasAttribute("platform_version", $infos);
        $this->assertObjectHasAttribute("device_name", $infos);
        $this->assertObjectHasAttribute("device_maker", $infos);
        $this->assertObjectHasAttribute("device_pointing_method", $infos);

    }

    public function testDetectFalse()
    {
        $ua_string = "Lorem ipsum dolor set";
        $infos = CUserAgent::detect($ua_string);
        $this->assertEquals($infos->browser, "Default Browser");
    }
}
