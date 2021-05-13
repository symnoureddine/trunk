<?php

namespace Ox\Core\Tests\Unit;

use Exception;
use Ox\Core\Kernel\CKernel;
use Ox\Core\Kernel\Event\CExceptionListener;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class CExceptionListenerTest extends UnitTestMediboard
{

    public function testExceptionListener(){
        $subscriber = new CExceptionListener();
        $req = new Request();
        $kernel = new CKernel($req);

        // gui
        $event = new ExceptionEvent($kernel, $req, CKernel::MASTER_REQUEST, new Exception('ipsum'));
        $subscriber->onException($event);
        $this->assertTrue($event->hasResponse());
        $this->assertInstanceOf(Response::class, $event->getResponse());

        // api
        $req->attributes->add(['is_api'=>true]);
        $event = new ExceptionEvent($kernel, $req, CKernel::MASTER_REQUEST, new Exception('ipsum'));
        $subscriber->onException($event);
        $this->assertTrue($event->hasResponse());
        $this->assertInstanceOf(JsonResponse::class, $event->getResponse());
    }
}
