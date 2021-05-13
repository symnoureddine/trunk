<?php
/**
 * @package Ox\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Event;

use Ox\Core\Kernel\Routing\CRouter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Router;

/**
 * Class CRouterListener
 */
class CRouterListener extends RouterListener
{

    public function __construct(Router $router = null, RequestStack $request_stack = null)
    {
        $router        = $router ?? CRouter::getInstance();
        $request_stack = $request_stack ?? new RequestStack();
        parent::__construct($router, $request_stack);
    }


    public static function getSubscribedEvents()
    {
        $events                        = parent::getSubscribedEvents();
        $events[KernelEvents::REQUEST] = [['onKernelRequest', 40]];

        return $events;
    }
}
