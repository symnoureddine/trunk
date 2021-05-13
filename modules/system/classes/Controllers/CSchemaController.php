<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Controllers;

use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CController;
use Ox\Core\CMbException;
use Ox\Core\CModelObject;
use Ox\Core\OpenApi\COpenApiException;
use Ox\Core\OpenApi\COpenApiManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CSystemController
 */
class CSchemaController extends CController
{
    /**
     * @param CRequestApi $request
     *
     * @param string      $resource_name
     *
     * @return Response
     * @throws CApiException
     * @throws CMbException
     * @api
     */
    public function models(CRequestApi $request, $resource_name)
    {
        $model = CModelObject::getClassNameByResourceName($resource_name);
        if (!class_exists($model)) {
            throw new CMbException('Class does not exists ' . $model);
        }
        if (!is_subclass_of($model, CModelObject::class)) {
            throw new CMbException('Class does not extends CModelObject ' . $model);
        }

        /** @var CModelObject $instance */
        $instance = new $model();
        $schema   = $instance->getSchema($request->getFieldsets());

        $resource = new CCollection($schema);
        $resource->setName('schema');

        return $this->renderApiResponse($resource);
    }

    /**
     * @param string $method
     * @param string $path (base_64 encoded)
     *
     * @return Response
     * @throws COpenApiException
     * @throws CMbException
     * @throws CApiException
     * @api
     */
    public function routes($method, $path): Response
    {
        $path = base64_decode($path);
        if (!$path[0] === '/') {
            $path = '/' . $path;
        }
        $openApi       = new COpenApiManager();
        $documentation = $openApi->getDocumentation();

        if (!array_key_exists($path, $documentation['paths'])) {
            throw new CMbException('Undefined path in OAS : ' . $path);
        }

        if (!array_key_exists($path, $documentation['paths'])) {
            throw new CMbException('Undefined method in OAS path : ' . $path);
        }

        $oas = $documentation['paths'][$path][$method];

        $resource = new CItem($oas);
        $resource->setName('route_schema');

        return $this->renderApiResponse($resource);
    }

}
