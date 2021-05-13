<?php
/**
 * @package Ox\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Installation;

use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CExceptionListener
 */
class CInstallationExceptionListener implements EventSubscriberInterface
{

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function handleException(GetResponseForExceptionEvent $event): void
    {
        $e = $event->getException();


        $status = $e instanceof CInstallationException ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        $data   = [
            'id'      => md5(serialize($e->getTraceAsString())),
            'status'  => $status,
            'message' => $e->getMessage(),
        ];

        $response = new JsonResponse($data, $status, [], false);

        $event->setResponse($response);
    }


    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [['handleException', 100]],
        ];
    }
}
