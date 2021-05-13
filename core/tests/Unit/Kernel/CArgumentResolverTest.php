<?php

use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\CStoredObject;
use Ox\Core\Kernel\Resolver\CArgumentResolver;
use Ox\Core\Kernel\Resolver\CRequestApiAttributeValueResolver;
use Ox\Core\Kernel\Resolver\CStoredObjectAttributeValueResolver;
use Ox\Core\Tests\Resources\CLoremIpsum;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\Controllers\CSystemController;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;


class CArgumentResolverTest extends UnitTestMediboard
{

    public function testConstruct()
    {
        $resolver = new CArgumentResolver();
        $this->assertInstanceOf(ArgumentResolverInterface::class, $resolver);

        return $resolver;
    }

    /**
     * @depends testConstruct
     */
    public function testCustomResolvers(CArgumentResolver $resolver)
    {
        $argument_resolver = $resolver->getArgumentResolver();
        $rc                = new ReflectionClass($argument_resolver);
        $prop              = $rc->getProperty('argumentValueResolvers');
        $prop->setAccessible(true);
        $resolvers_class = [];
        foreach ($prop->getValue($argument_resolver) as $_resolver) {
            $resolvers_class[] = get_class($_resolver);
        }
        $this->assertNotEmpty($resolvers_class);
        foreach ($resolver::CUSTOM_ARGUMENT_VALUE_RESOLVERS as $_resolver) {
            $this->assertContains($_resolver, $resolvers_class);
        }
    }


    public function testRequestApiAttributevalueResolverSuccess()
    {
        $argument = new ArgumentMetadata('request_api', CRequestApi::class, false, false, null);
        $request  = new Request();
        $resolver = new CRequestApiAttributeValueResolver();
        $this->assertTrue($resolver->supports($request, $argument));
        $resoled = $resolver->resolve($request, $argument);
        $this->assertInstanceOf(Generator::class, $resoled);
        $this->assertInstanceOf(CRequestApi::class, $resoled->current());
    }

    public function testRequestApiAttributevalueResolverFailed()
    {
        $argument = new ArgumentMetadata('request_api', CLoremIpsum::class, false, false, null);
        $request  = new Request();
        $resolver = new CRequestApiAttributeValueResolver();
        $this->assertFalse($resolver->supports($request, $argument));
    }

    public function testStoredObjectAttributeValueresolverSuccess()
    {
        $user     = $this->getRandomObjects(CUser::class, 1);
        $argument = new ArgumentMetadata('stored_object', CStoredObject::class, false, false, null);
        $request  = new Request();
        $request->attributes->add(['object_class' => CUser::class]);
        $request->attributes->add(['user_id' => $user->_id]);
        $request->attributes->add(['_controller' => CSystemController::class.'::locales']);


        $resolver = new CStoredObjectAttributeValueResolver();
        $this->assertTrue($resolver->supports($request, $argument));
        $resoled = $resolver->resolve($request, $argument);
        $this->assertInstanceOf(Generator::class, $resoled);
        $this->assertInstanceOf(CStoredObject::class, $resoled->current());
        $this->assertInstanceOf(CUser::class, $resoled->current());
    }

    public function testStoredObjectAttributeValueresolverFailed()
    {
        $argument = new ArgumentMetadata('stored_object', CLoremIpsum::class, false, false, null);
        $request  = new Request();
        $resolver = new CStoredObjectAttributeValueResolver();
        $this->assertFalse($resolver->supports($request, $argument));
    }
}
