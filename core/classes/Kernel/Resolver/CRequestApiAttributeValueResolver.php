<?php
/**
 * @package Ox\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Resolver;


use Ox\Core\Api\Exceptions\CApiRequestException;
use Ox\Core\Api\Request\CRequestApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Responsible for resolving the value of an argument based on its metadata.
 * {@inheritdoc}
 */
final class CRequestApiAttributeValueResolver implements ArgumentValueResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return CRequestApi::class === $argument->getType() || is_subclass_of($argument->getType(), CRequestApi::class);
    }

    /**
     * @inheritDoc
     * @throws CApiRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $request_api = new CRequestApi($request);
        yield $request_api;
    }
}
