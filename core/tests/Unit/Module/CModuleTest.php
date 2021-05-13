<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */
namespace Ox\Core\Tests\Unit\Module;

use Ox\Core\Module\CModule;
use Ox\Core\SHM;
use Ox\Tests\UnitTestMediboard;


class CModuleTest extends UnitTestMediboard
{

    public function testGetInstalledSuccess()
    {
        $module = CModule::getInstalled('system');
        $this->assertInstanceOf(CModule::class, $module);
    }

    public function testGetInstalledFailed()
    {
        $module = CModule::getInstalled('lorem');
        $this->assertNull($module);
    }

    public function testGetActiveSuccess()
    {
        $module = CModule::getActive('system');
        $this->assertInstanceOf(CModule::class, $module);
    }

    public function testGetActiveFailed()
    {
        $module = CModule::getActive('lorem');
        $this->assertNull($module);
    }

    public function testRegisterTabs()
    {
        $module = CModule::getActive('system');
        $this->assertEmpty($module->_tabs);
        SHM::remKeys(CModule::class . '::registerTabs*');
        $module->registerTabs();
        $this->assertNotEmpty($module->_tabs);
        return $module;
    }

    /**
     * @depends testRegisterTabs
     */
    public function testRegisterFromCache(CModule $module)
    {
        $module->_tabs = [];
        $module->registerTabs();
        $this->assertNotEmpty($module->_tabs);
    }
}
