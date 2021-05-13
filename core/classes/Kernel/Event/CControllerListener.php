<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Event;

use Exception;
use Ox\Core\CController;
use Ox\Core\Kernel\CKernel;
use Ox\Core\Kernel\Exception\CControllerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CControllerListener
 */
class CControllerListener implements EventSubscriberInterface
{

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST  => ['onRequest', 30],
            KernelEvents::RESPONSE => ['onResponse', 90],
        ];
    }

    /**
     * Add Subscriber in Kernel Event Dispatcher
     *
     * @param RequestEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function onRequest(RequestEvent $event)
    {
        $controller = CController::getControllerFromRequest($event->getRequest());

        $subscribers = $controller->getEventSubscribers();

        if (empty($subscribers)) {
            return;
        }

        $event_dispatcher = CKernel::getInstance()->getDispatcher();

        // Check subscribers
        foreach ($subscribers as $subscriber) {
            if (!$subscriber instanceof EventSubscriberInterface) {
                $class = get_class($subscriber);
                throw new CControllerException(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    "Invalid event subscriber interface {$class}."
                );
            }

            foreach ($subscriber::getSubscribedEvents() as $eventName => $value) {
                if ($eventName === KernelEvents::REQUEST) {
                    throw new CControllerException(
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                        "Invalid kernel event {$eventName}."
                    );
                }
            }

            // Add subscriber
            $event_dispatcher->addSubscriber($subscriber);
        }
    }


    /**
     * Set custom headers
     *
     * @param ResponseEvent $event
     *
     * @return void
     */
    public function onResponse(ResponseEvent $event)
    {
        $response_headers = $event->getResponse()->headers;

        foreach (CController::getHeaders($event->getRequest()) as $header_name => $header_value) {
            $response_headers->set($header_name, $header_value);
        }
    }
}
