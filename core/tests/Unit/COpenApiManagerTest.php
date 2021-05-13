<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit;

use Ox\Core\Kernel\Exception\CRouteException;
use Ox\Core\Kernel\Routing\CRouteManager;
use Ox\Core\OpenApi\COpenApiException;
use Ox\Core\OpenApi\COpenApiManager;
use Ox\Tests\UnitTestMediboard;

/**
 * Class COpenApiManagerTest
 */
class COpenApiManagerTest extends UnitTestMediboard
{

    /**
     * @throws COpenApiException
     * @throws CRouteException
     * @group schedules
     */
    public function testBuild()
    {
        $route_manager = new CRouteManager();
        $routes        = $route_manager->loadAllRoutes()->getRouteCollection();
        $manager       = new COpenApiManager();

        $msg = $manager->build($routes);
        $this->assertTrue($manager->documentationExists());
        $this->assertStringStartsWith('Generated openapi documentation file in', $msg);

        return $manager;
    }

    /**
     * @depends testBuild
     *
     * @param COpenApiManager $manager
     * @group schedules
     * @throws COpenApiException
     */
    public function testGetDocumentation(COpenApiManager $manager)
    {
        $this->assertIsArray($manager->getDocumentation());
    }

}
