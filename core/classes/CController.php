<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Exception;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestFormats;
use Ox\Core\Api\Resources\CAbstractResource;
use Ox\Core\Api\Serializers\CJsonApiSerializer;
use Ox\Core\Kernel\Exception\CControllerException;
use Ox\Core\Kernel\Routing\CRouter;
use Ox\Core\Module\CModule;
use Ox\Core\Vue\OxSmarty;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Spatie\ArrayToXml\ArrayToXml;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;

/**
 * Class CController
 */
class CController
{
    /**
     * @param mixed $var
     *
     * @return void
     */
    public function dump($var): void
    {
        CApp::dump($var);
    }

    /**
     * @param string $content
     * @param int    $status  The response status code
     * @param array  $headers An array of response headers
     *
     * @return Response
     */
    public function renderResponse($content, $status = 200, $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }

    /**
     * @param mixed $data    The response data
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     * @param bool  $json    If the data is already a JSON string
     *
     * @return JsonResponse
     */
    public function renderJsonResponse($data, $status = 200, $headers = [], $json = true): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $json);
    }

    /**
     * @param array $data
     * @param int   $status
     * @param array $headers
     * @param bool  $convert
     *
     * @return Response
     */
    public function renderXmlResponse(array $data, $status = 200, $headers = [], $convert = true): Response
    {
        if ($convert) {
            $data = ArrayToXml::convert($data);
        }

        $response = new Response($data, $status, $headers);
        $response->headers->set('content-type', CRequestFormats::FORMAT_XML);

        return $response;
    }

    public function renderVueResponse(
        string $template,
        array $vars = [],
        int $status = 200,
        array $headers = []
    ): Response {
        $smarty = new OxSmarty($this->getRootDir());
        $smarty->assign($vars);
        $html = $smarty->fetch($template);

        return new Response($html, $status, $headers);
    }

    /**
     * @param CAbstractResource $resource
     *
     * @param int               $status
     * @param array             $headers
     *
     * @return Response
     */
    public function renderApiResponse(CAbstractResource $resource, $status = 200, $headers = []): Response
    {
        switch ($resource->getFormat()) {
            // XML
            case CRequestFormats::FORMAT_XML:
                $datas    = $resource->xmlSerialize();
                $response = $this->renderXmlResponse($datas, $status, $headers);
                break;
            // JSON
            case CRequestFormats::FORMAT_JSON:
            default:
                $response = $this->renderJsonResponse($resource, $status, $headers, false);
                $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);
                if ($resource->getSerializer() === CJsonApiSerializer::class) {
                    $response->headers->set('content-type', CRequestFormats::FORMAT_JSON_API);
                }
        }

        if ($resource->isEtaggable()) {
            $response->setEtag($resource->getEtag());
        }

        return $response;
    }

    /**
     * @param string $file The file path
     *
     * @return JsonResponse|Response
     * @throws CControllerException
     */
    public function renderFileResponse($file)
    {
        if (!file_exists($file)) {
            throw new CControllerException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Invalid file ' . $file);
        }

        $infos   = pathinfo($file);
        $content = file_get_contents($file);

        switch ($infos['extension']) {
            case 'json':
                return $this->renderJsonResponse($content);
            case 'html':
            case 'htm':
            default:
                return $this->renderResponse($content);
        }
    }

    /**
     * @return array|EventSubscriberInterface
     */
    public function getEventSubscribers()
    {
        return [];
    }

    /**
     * @return string|null The module name
     * @throws Exception
     */
    public function getModuleName(): ?string
    {
        return CClassMap::getInstance()->getClassMap(static::class)->module;
    }

    /**
     * @return ReflectionClass
     * @throws CControllerException
     */
    public function getReflectionClass(): ?ReflectionClass
    {
        try {
            return new ReflectionClass($this);
        } catch (ReflectionException $e) {
            throw new CControllerException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Unable to construct the ReflectionClass'
            );
        }
    }

    /**
     * @param string $method_name
     *
     * @return ReflectionMethod
     * @throws CControllerException
     */
    public function getReflectionMethod($method_name): ?ReflectionMethod
    {
        try {
            return ($this->getReflectionClass())->getMethod($method_name);
        } catch (ReflectionException $e) {
            throw new CControllerException(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * Get CController onstance from attributes _controller in Request
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CControllerException
     */
    public static function getControllerFromRequest(Request $request)
    {
        $_controller = $request->attributes->get('_controller');
        $_controller = explode('::', $_controller);
        $_controller = $_controller[0];
        if (!class_exists($_controller)) {
            throw new CControllerException(Response::HTTP_INTERNAL_SERVER_ERROR, "Invalid controller {$_controller}.");
        }

        return new $_controller;
    }

    /**
     * @return array
     */
    public static function getHeaders(): array
    {
        return [
            'Expires'         => 'Mon, 26 Jul 1997 05:00:00 GMT', // Date in the past
            'Last-Modified'   => gmdate('D, d M Y H:i:s') . ' GMT',  // always modified
            'Cache-Control'   => 'no-cache, no-store, must-revalidate', // HTTP/1.1
            'Pragma'          => 'no-cache',  // HTTP/1.0
            'X-UA-Compatible' => 'IE=edge',  // Force IE document mode
            'X-Request-ID'    => CApp::getRequestUID(),  // Correlates HTTP requests between a client and server
        ];
    }

    /**
     * @param Route $route
     *
     * @return CController
     * @throws CControllerException
     */
    public static function getControllerFromRoute(Route $route)
    {
        $controller_name = $route->getDefault('_controller');

        $_controller = explode('::', $controller_name);
        $_controller = $_controller[0];
        if (!class_exists($_controller)) {
            throw new CControllerException(Response::HTTP_INTERNAL_SERVER_ERROR, "Invalid controller {$_controller}.");
        }

        return new $_controller;
    }

    /**
     * Get method
     *
     * @param Route $route
     *
     * @return string
     * @throws CControllerException
     */
    public static function getMethodFromRoute(Route $route)
    {
        $controller_name = $route->getDefault('_controller');

        $_controller = explode('::', $controller_name);
        $method      = $_controller[1];
        $controller  = $_controller[0];

        if (!method_exists(new $controller, $method)) {
            throw new CControllerException(Response::HTTP_INTERNAL_SERVER_ERROR, "Invalid method {$method}.");
        }

        return $method;
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     */
    protected function redirect(string $url, int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     */
    protected function redirectToRoute(
        string $route,
        array $parameters = [],
        int $status = Response::HTTP_FOUND
    ): RedirectResponse {
        $url = CRouter::generateUrl($route, $parameters);

        return $this->redirect($url, $status);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @see UrlGeneratorInterface
     */
    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return CRouter::generateUrl($route, $parameters, $referenceType);
    }

    public function checkPermEdit(CStoredObject $object, Request $request = null): bool
    {
        $can = $object->canDo();

        return (bool)$can->edit;
    }

    public function checkPermRead(CStoredObject $object, Request $request = null): bool
    {
        $can = $object->canDo();

        return (bool)$can->read;
    }

    protected function getActiveModule(string $mod_name): string
    {
        // dP add super hack
        if (CModule::getActive($mod_name) === null) {
            $mod_name = 'dP' . $mod_name;

            if (CModule::getActive($mod_name) === null) {
                throw new CApiException("Module '{$mod_name}' does not exists or is not active");
            }
        }

        return $mod_name;
    }
}
