<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Controllers;

use Ox\Core\Api\Etag\CEtag;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\CMbObject;
use Ox\Core\Kernel\Event\CEtagListener;
use Ox\Core\SHM;
use Ox\Core\Tests\Unit\Api\UnitTestRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
abstract class AbstractControllerRequestTest extends UnitTestRequest
{
    abstract protected function prepareRequest(Request $attribute_request): Request;

    abstract protected function getArgsForRequest(): array;

    public function setUp(): void
    {
        parent::setUp();
        // clear etag cache
        SHM::remKeys(CEtag::CACHE_PREFIX . '*');

        $this->addListener(new CEtagListener());
    }

    public function testRequestWithoutSearchResponseHasETag(): void
    {
        $request = $this->prepareRequest(new Request());

        $response = $this->handleRequest($request);
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getContent());
        $this->assertNotNull($response->getEtag());
    }

    public function testRequestWithArgsResponseHasNoETag(): void
    {
        $request = $this->prepareRequest(new Request($this->getArgsForRequest()));

        $response = $this->handleRequest($request);
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getContent());
        $this->assertNull($response->getEtag());
    }

    public function testRequestWithSearchReturnNotModifiedOnExistingETag(): void
    {
        $request = $this->prepareRequest(new Request());

        $response = $this->handleRequest($request);
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getContent());

        $etag = $response->getEtag();
        $this->assertNotNull($etag);

        $request->headers->set('if_none_match', $etag);

        $response = $this->handleRequest($request);
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('{}', $response->getContent());
    }
}
