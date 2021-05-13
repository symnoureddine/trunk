<?php
/**
 * @package Mediboard\\core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Event;

use Ox\Core\Api\Etag\CEtag;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\CDevtools;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CCorsListener
 */
final class CEtagListener implements EventSubscriberInterface
{

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST  => ['onRequest', 35],
            KernelEvents::RESPONSE => ['onResponse', 110],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @return JsonResponse
     * @throws CApiException
     */
    public function onRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // Request may have multiples if_none_match headers (etag)
        foreach ($request->getETags() as $request_etag) {
            $etag = new CEtag($request_etag, $request->getRequestUri());

            if ($etag->checkValidity()) {
                $response = $this->getNotModifiedResponse($request_etag);
                $event->setResponse($response);
                $event->stopPropagation();
            }
        }
    }

    private function getNotModifiedResponse($etag): JsonResponse
    {
        return (new JsonResponse(null, Response::HTTP_NOT_MODIFIED))->setEtag($etag);
    }


    /**
     * @param ResponseEvent $event
     *
     * @return JsonResponse|null
     * @throws CApiException
     */
    public function onResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        if (!$response->getEtag() || $response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
            return null;
        }

        // Response have etag header (we cache it for next call)
        $etag = new CEtag($response->getEtag(), $event->getRequest()->getRequestUri());
        $etag->cache();

        $response->setEtag((string) $etag); // without type
        $event->setResponse($response);
    }
}
