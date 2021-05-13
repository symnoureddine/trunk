<?php

/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Request;

use Ox\Core\Api\Request\CRequestGroup;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CRequestGroupTest extends UnitTestMediboard
{
    /**
     * @dataProvider constructProvider
     */
    public function testConstruct(Request $request, ?int $group_id): void
    {
        $request_group = new CRequestGroup($request);
        $this->assertEquals($group_id, $request_group->getGroup()->_id);
    }

    public function testConstructWithSession(): void
    {
        $group = $this->getRandomObjects(CGroups::class);
        $_SESSION['g'] = $group->_id;

        $this->testConstruct(new Request(), (int)$group->_id);
    }

    public function testConstructWithoutGroup(): void
    {
        $group = CGroups::loadCurrent();
        $_SESSION['g'] = null;

        $this->testConstruct(new Request(), (int)$group->_id);
    }

    public function testConstructWithGroupNotExists(): void
    {
        $request = new Request();
        $request->headers->set(CRequestGroup::HEADER_GROUP, PHP_INT_MAX);

        $this->expectExceptionMessage('common-error-Object not found');

        new CRequestGroup($request);
    }

    public function constructProvider(): array
    {
        $groups = $this->getRandomObjects(CGroups::class, 3);

        $provider = [];

        $group = array_pop($groups);
        $request = new Request();
        $request->headers->set(CRequestGroup::HEADER_GROUP, $group->_id);
        $provider['with_header'] = [$request, (int)$group->_id];

        $group = array_pop($groups);
        $provider['with_get_1'] = [new Request([CRequestGroup::QUERY_GROUP[0] => $group->_id]), (int)$group->_id];

        $group = array_pop($groups);
        $provider['with_get_2'] = [new Request([CRequestGroup::QUERY_GROUP[1] => $group->_id]), (int)$group->_id];

        return $provider;
    }
}
