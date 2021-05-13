<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit;

use Ox\Core\CPermission;
use Ox\Mediboard\System\Controllers\CSystemController;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**

 */
class CPermissionTest extends UnitTestMediboard
{

    public function testConstruct(){
        $controller = new CSystemController();
        $req = new Request();
        $req->attributes->add(['permssion'=> PERM_READ]);
        $permission = new CPermission($controller, $req);
        $this->assertEquals($permission->module_name, 'system');
        return $permission;
    }

    /**
     * @param CPermission $permission
     * @depends testConstruct
     */
    public function testCheckSuccess(CPermission $permission){
        $this->assertNull($permission->check());
    }
}
