<?php

namespace Ox\Core\Tests\Unit;

use Ox\Core\Kernel\Routing\CRequestFactory;
use Ox\Tests\UnitTestMediboard;

/**
 * CRouteManagerTest
 */
class CRequestFactoryTest extends UnitTestMediboard
{


    public function testApi()
    {
        $_SERVER['REQUEST_URI'] = '/api/lorem/ipsum';
        $request                = CRequestFactory::createFromGlobals();
        $this->assertTrue($request->attributes->getBoolean('is_api'));
    }


    public function testGui()
    {
        $_SERVER['REQUEST_URI'] = '/gui/lorem/ipsum';
        $request                = CRequestFactory::createFromGlobals();
        $this->assertFalse($request->attributes->getBoolean('is_api'));
    }

}
