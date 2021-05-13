<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Etag;


use Ox\Core\Api\Etag\CEtag;
use Ox\Core\Cache;
use Ox\Core\Kernel\Event\CEtagListener;
use Ox\Core\Kernel\Event\CEventDispatcher;
use Ox\Core\SHM;
use Ox\Core\Tests\Unit\Api\UnitTestRequest;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;

/**
 * @group schedules
 * @runTestsInSeparateProcesses
 */
class CEtagRequestTest extends UnitTestRequest
{
    public function setUp(): void
    {
        parent::setUp();
        // clear etag cache
        SHM::remKeys(CEtag::CACHE_PREFIX . '*');

        $this->addListener(new CEtagListener());
    }

    public function testRequestWithoutEtagResponseWithoutEtag()
    {
        $request = Request::create('/lorem/ispum');

        $request->attributes->add([
            '_route' => 'lorem',
            '_controller' => CEtagController::class . '::jsonWithoutEtag',
        ]);

        $response = $this->handleRequest($request);
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getContent());
        $this->assertNull($response->getEtag());
        $this->assertEmpty(SHM::listKeys(CEtag::CACHE_PREFIX . '*'));
    }


    public function testRequestWithoutEtagResponseWithEtag()
    {
        $request = Request::create('/lorem/ispum');

        $request->attributes->add([
            '_route' => 'lorem',
            '_controller' => CEtagController::class . '::jsonWithEtag',
        ]);

        $response = $this->handleRequest($request);
        $response_etag = str_replace('"', '', $response->getEtag());

        $this->assertEquals($response_etag, CEtagController::getEtag());
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getContent());

        $cache = new Cache(CEtag::CACHE_PREFIX, $response_etag, Cache::INNER_OUTER);
        $this->assertTrue($cache->exists());
        $this->assertContains('/lorem/ispum', $cache->get());
    }

    public function testRequestWithEtagResponseWithoutEtag()
    {
        $request = Request::create('/lorem/ispum');
        $request->headers->set('if_none_match', '1234536789AZERTY');
        $request->attributes->add([
            '_route' => 'lorem',
            '_controller' => CEtagController::class . '::jsonWithEtag',
        ]);

        $response = $this->handleRequest($request);

        $this->assertNotNull($response->getEtag());
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getContent());

    }

    public function testRequestWithEtagResponseWithEtag()
    {
        $request = Request::create('/lorem/ispum?filter=1');
        $request->attributes->add([
            '_route' => 'lorem',
            '_controller' => CEtagController::class . '::jsonWithEtag',
        ]);

        // first request
        $response_1 = $this->handleRequest($request);
        $response_etag = str_replace('"', '', $response_1->getEtag());

        $this->assertNotNull($response_etag);
        $this->assertEquals($response_1->getStatusCode(), 200);
        $this->assertNotNull($response_1->getContent());

        $request->headers->set('if_none_match', $response_etag);
        $response_2 = $this->handleRequest($request);
        $this->assertEquals(304, $response_2->getStatusCode());
        $this->assertEquals($response_2->getContent(), '{}');

    }

    public function testEtagCacheTagging()
    {
        $request = Request::create('/foo/bar?filter=1');
        $request->attributes->add([
            '_route' => 'lorem',
            '_controller' => CEtagController::class . '::jsonWithEtagTyped',
        ]);
        $request->headers->set('if_none_match', CEtagController::getEtag());

        // first request
        $response_1 = $this->handleRequest($request);
        $this->assertEquals(200, $response_1->getStatusCode());
        $response_2 = $this->handleRequest($request);
        $this->assertEquals(304, $response_2->getStatusCode());

        $cache = new Cache(CEtag::CACHE_PREFIX, CEtagController::getEtag(), Cache::INNER_OUTER);
        $this->assertTrue($cache->exists());
        $this->assertContains('/foo/bar?filter=1', $cache->get());

        $cache = new Cache(CEtag::CACHE_PREFIX_TAGGING, CEtag::TYPE_LOCALES, Cache::INNER_OUTER);
        $this->assertTrue($cache->exists());
        $this->assertContains(CEtagController::getEtag(), $cache->get());


        // second request
        $request = Request::create('/foo/bar?filter=2');
        $request->attributes->add([
            '_route' => 'lorem',
            '_controller' => CEtagController::class . '::jsonWithEtagTyped',
        ]);
        $request->headers->set('if_none_match', CEtagController::getEtag());
        $response_1 = $this->handleRequest($request);
        $this->assertEquals(200, $response_1->getStatusCode());
        $response_2 = $this->handleRequest($request);
        $this->assertEquals(304, $response_2->getStatusCode());

        $cache = new Cache(CEtag::CACHE_PREFIX_TAGGING, CEtag::TYPE_LOCALES, Cache::INNER_OUTER);
        $this->assertTrue($cache->exists());
        $this->assertCount(1, $cache->get());
    }
}
