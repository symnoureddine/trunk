<?php
/**
 * @package Mediboard\\core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Event;

use Ox\Core\Auth\TokenAuthentication;
use Ox\Core\Kernel\Routing\CRouteManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CCorsListener
 */
final class CCorsListener implements EventSubscriberInterface
{

    /** @var bool */
    private $is_request_options = false;

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST  => ['onRequest', 9999],
            KernelEvents::RESPONSE => ['onResponse', 9999],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @return void
     */
    public function onRequest(RequestEvent $event): void
    {
        // Only on the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $method  = $request->getRealMethod();

        if (Request::METHOD_OPTIONS === $method) {
            $this->is_request_options = true;
            $response                 = new Response();
            $response->setStatusCode('204', 'No content');
            $event->setResponse($response);
            $event->stopPropagation();
        }
    }

    /**
     * @param ResponseEvent $event
     *
     * @return void
     */
    public function onResponse(ResponseEvent $event): void
    {
        // Only on the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->is_request_options) {
            $event->stopPropagation();
        }

        $response = $event->getResponse();
        $request  = $event->getRequest();
        if ($response) {
            $allow_headers = 'Accept, Content-Type, Authorization, ' . TokenAuthentication::HEADER_KEY;
            $response->headers->set('Access-Control-Allow-Methods', CRouteManager::ALLOWED_METHODS);
            $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin', '*'));
            $response->headers->set('Access-Control-Allow-Headers', $allow_headers);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
    }
}
