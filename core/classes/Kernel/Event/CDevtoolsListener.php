<?php
/**
 * @package Ox\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Event;

use Exception;
use Ox\Core\CDevtools;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CDevtoolsListener
 */
class CDevtoolsListener implements EventSubscriberInterface
{

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST  => [['onRequest', 90]],
            KernelEvents::RESPONSE => [['onResponse', 50]],
        ];
    }


    /**
     * @param RequestEvent $event
     *
     * @return void
     */
    public function onRequest(RequestEvent $event)
    {
        // Only on the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        CDevtools::start($request);
    }


    /**
     * @param ResponseEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function onResponse(ResponseEvent $event)
    {
        // Only on the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        CDevtools::stop($response);
    }
}
