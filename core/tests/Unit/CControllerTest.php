<?php

namespace Ox\Core\Tests\Unit;

use Exception;
use InvalidArgumentException;
use Ox\Core\Api\Etag\CEtag;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CController;
use Ox\Core\Kernel\Exception\CControllerException;
use Ox\Core\Kernel\Routing\CRouter;
use Ox\Interop\Fhir\Controllers\CFHIRController;
use Ox\Mediboard\Admin\Controllers\PermissionController;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\Controllers\CSystemController;
use Ox\Tests\UnitTestMediboard;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class CControllerTest extends UnitTestMediboard
{

    const CONTROLLER      = CFHIRController::class;
    const FUNCTION        = 'getEventSubscribers';
    const FUNCTION_FAILED = 'failedTestFunction';
    const MODULE          = 'fhir';
    const ROUTE           = 'fhir_metadata';

    /**
     * RenderResponse
     */
    public function testRenderResponse()
    {
        $controller = $this->getController();
        $content    = 'Lorem ipsum';
        $response   = $controller->renderResponse($content);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($response->getContent(), $content);
    }

    /**
     * GetModule
     *
     * @throws Exception
     */
    public function testGetModule()
    {
        $controller = $this->getController();
        $module     = $controller->getModuleName();
        $this->assertEquals($module, self::MODULE);
    }

    /**
     * GetControllerFromRequest
     *
     * @throws CControllerException
     */
    public function testGetControllerFromRequest()
    {
        $route = new Route('/lorem/ipsum');
        $route->setDefault('_controller', self::CONTROLLER);
        $collection = new RouteCollection();
        $collection->add('a', $route);

        $request    = Request::create('/lorem/ipsum');
        $matcher    = new UrlMatcher($collection, new RequestContext);
        $parameters = $matcher->matchRequest($request);
        $request->attributes->add($parameters);

        $controller = CController::getControllerFromRequest($request);
        $this->assertEquals($controller, $this->getController());
    }

    /**
     *
     */
    public function testGetReflectionMethod()
    {
        $controller = self::CONTROLLER;
        /** @var CController $controller */
        $controller = new $controller;
        $this->assertInstanceOf(ReflectionMethod::class, $controller->getReflectionMethod(self::FUNCTION));
    }

    /**
     *
     */
    public function testGetReflectionClass()
    {
        $controller = self::CONTROLLER;
        /** @var CController $controller */
        $controller = new $controller;
        $this->assertInstanceOf(ReflectionClass::class, $controller->getReflectionClass());
    }

    /**
     * RenderJsonRespons
     */
    public function testRenderJsonResponse()
    {
        $controller = $this->getController();
        $content    = ['foo' => 'bar'];
        $response   = $controller->renderJsonResponse($content, 200, [], false);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($response->getContent(), json_encode($content));
    }


    /**
     * GetEventSubscriber
     */
    public function testGetEventSubscribers()
    {
        $controller = $this->getController();
        $events     = $controller->getEventSubscribers();
        $this->assertInstanceOf(EventSubscriberInterface::class, $events[0]);
    }

    /**
     * GetControllerFromRouteOk
     */
    public function testGetControllerFromRouteOk()
    {
        $this->assertInstanceOf(CController::class, CController::getControllerFromRoute($this->getRoute()));
    }

    /**
     * GetControllerFromRouteKo
     */
    public function testGetControllerFromRouteKo()
    {
        $this->expectException(CControllerException::class);
        CController::getControllerFromRoute(new Route('api', []));
    }

    /**
     * GetMethodFromRouteOk
     */
    public function testGetMethodFromRouteOk()
    {
        $this->assertEquals(self::FUNCTION, CController::getMethodFromRoute($this->getRoute()));
    }

    /**
     * GetMethodFromRouteKo
     */
    public function testGetMethodFromRouteKo()
    {
        $this->expectException(CControllerException::class);
        CController::getMethodFromRoute($this->getRouteFailed());
    }

    /**
     * @return CController
     */
    private function getController()
    {
        $controller = self::CONTROLLER;

        return new $controller;
    }

    /**
     * @return Route
     */
    private function getRoute()
    {
        return new Route('http::localhost', ['_controller' => self::CONTROLLER . '::' . self::FUNCTION]);
    }

    /**
     * @return Route
     */
    private function getRouteFailed()
    {
        return new Route('http::localhost', ['_controller' => self::CONTROLLER . '::' . self::FUNCTION_FAILED]);
    }

    public function testGenerateUrl()
    {
        $controller = new PermissionController();
        $url        = $this->invokePrivateMethod($controller, 'generateUrl', 'admin_identicate');
        $this->assertEquals(CRouter::generateUrl('admin_identicate'), $url);
    }

    public function testRedirect()
    {
        $controller = new PermissionController();
        $target_url = 'http://www.loremi-ipsum.fr';
        /** @var RedirectResponse $response */
        $response = $this->invokePrivateMethod($controller, 'redirect', $target_url);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($target_url, $response->getTargetUrl());
    }

    public function testRedirectToRoute()
    {
        $controller = new PermissionController();
        /** @var RedirectResponse $response */
        $response = $this->invokePrivateMethod($controller, 'redirectToRoute', 'admin_identicate', [], 302);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(CRouter::generateUrl('admin_identicate'), $response->getTargetUrl());
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testRedirectToRouteFailed()
    {
        $controller = new PermissionController();
        $this->expectException(InvalidArgumentException::class);
        $this->invokePrivateMethod($controller, 'redirectToRoute', 'system_about', [], 404);
    }

    public function testRenderApiResponse(){
        $controller = new CSystemController();
        $resource = new CItem(new CUser());
        $resource->setEtag(CEtag::TYPE_LOCALES);
        $reponse = $controller->renderApiResponse($resource);
        $this->assertInstanceOf(JsonResponse::class, $reponse);
        $this->assertNotNull($reponse->getEtag());
    }
}
