<?php

/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

/**
 * Class CArgumentResolver
 */
class CArgumentResolver implements ArgumentResolverInterface
{

    /** @var ArgumentResolver */
    private $argument_resolver;

    /** @var string[] */
    public const CUSTOM_ARGUMENT_VALUE_RESOLVERS = [
        CRequestApiAttributeValueResolver::class,
        CStoredObjectAttributeValueResolver::class,
    ];

    /**
     * CArgumentResolver constructor.
     */
    public function __construct()
    {
        $resolvers = array_merge(
            ArgumentResolver::getDefaultArgumentValueResolvers(),
            $this->getCustomArgumentValueResolvers()
        );

        $this->argument_resolver = new ArgumentResolver(null, $resolvers);
    }

    /**
     * @return array
     */
    public function getCustomArgumentValueResolvers(): array
    {
        $retour = [];
        foreach (static::CUSTOM_ARGUMENT_VALUE_RESOLVERS as $_resolver) {
            $retour[] = new $_resolver();
        }

        return $retour;
    }

    /**
     * @return ArgumentResolver
     */
    public function getArgumentResolver(): ArgumentResolver
    {
        return $this->argument_resolver;
    }

    /**
     * @param Request  $request
     * @param callable $controller
     *
     * @return array
     */
    public function getArguments(Request $request, $controller)
    {
        return $this->argument_resolver->getArguments($request, $controller);
    }
}
