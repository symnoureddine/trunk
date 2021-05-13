<?php
/**
 * @package Ox\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Resolver;

use Exception;
use Ox\Core\CController;
use Ox\Core\CStoredObject;
use Ox\Core\Kernel\Exception\CHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Responsible for resolving the value of an argument based on its metadata.
 * {@inheritdoc}
 */
final class CStoredObjectAttributeValueResolver implements ArgumentValueResolverInterface
{
    private $object_class;
    private $object_id;

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        $this->object_class = $argument->getType();

        if ($this->object_class === CStoredObject::class) {
           $this->object_class = $request->attributes->get('object_class');
        }

        if (!is_subclass_of(
                $this->object_class,
                CStoredObject::class
            )) {
            return false;
        }

        /** @var CStoredObject $object */
        $object = new $this->object_class();
        $object_primary_key = $object->getPrimaryKey();

        if (!$this->object_id = $request->attributes->getInt($object_primary_key)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function resolve(Request $request, ArgumentMetadata $argument): ?\Generator
    {
        /** @var CStoredObject $object */
        $object = $this->object_class::findOrFail($this->object_id);

        /** @var CController $controller */
        $controller = CController::getControllerFromRequest($request);

        // check perm
        switch ($request->getMethod()) {
            case Request::METHOD_GET:
            case Request::METHOD_HEAD:
            default:
                // check read
                $perm = $controller->checkPermRead($object, $request);
                break;

            case Request::METHOD_POST:
            case Request::METHOD_PUT:
            case Request::METHOD_DELETE:
            case Request::METHOD_PATCH:
                // check edit
                $perm = $controller->checkPermEdit($object, $request);
                break;
        }
        if (!$perm) {
            throw new CHttpException(Response::HTTP_FORBIDDEN, 'Permission denied');
        }

        yield $object;
    }
}
