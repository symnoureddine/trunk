<?php

namespace Ox\Core\Tests\Unit;

use Ox\Core\Kernel\Routing\CRouter;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\Routing\Router;

/**
 * CRouteManagerTest
 */
class CRouterTest extends UnitTestMediboard
{


    public function testSingelton()
    {
        $instance = CRouter::getInstance();
        $this->assertEquals($instance, CRouter::getInstance());
        $this->assertInstanceOf(Router::class, $instance);
    }

    public function testOptionsIsSet()
    {
        $instance = CRouter::getInstance();
        $this->assertNotNull($instance->getOption('cache_dir'));
        $this->assertNotNull($instance->getOption('generator_cache_class'));
        $this->assertNotNull($instance->getOption('matcher_cache_class'));
    }

}
