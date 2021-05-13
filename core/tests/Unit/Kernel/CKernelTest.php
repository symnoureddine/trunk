<?php
namespace Ox\Core\Tests\Unit;

use CLoremIpsumController;
use Ox\Core\Kernel\CKernel;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernel;

/**
 * Class CKernelTest
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CKernelTest extends UnitTestMediboard
{

    /**
     * @return Request
     */
    private function makeRequest()
    {
        $req        = new Request();
        $attributes = [
            '_route'      => 'lorem',
            '_controller' => CLoremIpsumController::class,
        ];
        $req->attributes->add($attributes);

        return $req;
    }

    public function testConstruct()
    {
        $req      = $this->makeRequest();
        $kernel   = new CKernel($req);
        $instance = CKernel::getInstance();
        $this->assertInstanceOf(HttpKernel::class, $instance);
        $this->assertInstanceOf(EventDispatcher::class, $instance->getDispatcher());
        $this->assertInstanceOf(Request::class, $instance->getMasterRequest());
    }


    public function testGetInstanceFailed()
    {
        $this->expectExceptionMessage('Kernel is not instantiated');
        CKernel::getInstance();
    }

    public function testSingleton()
    {
        $req    = new Request();
        $kernel = new CKernel($req);
        $this->expectExceptionMessage('Kernel is already instantiated');
        $kernel2 = new CKernel($req);
    }

    public function testHandleRequest()
    {
        $req      = $this->makeRequest();
        $kernel   = new CKernel($req);
        $response = $kernel->handleRequest();
        $this->assertInstanceOf(Response::class, $response);
    }
}
