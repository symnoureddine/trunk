<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Event;

use Exception;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestFormats;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Api\Serializers\CErrorSerializer;
use Ox\Core\CController;
use Ox\Core\CError;
use Ox\Core\Kernel\Exception\CHttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CExceptionListener
 */
class CExceptionListener implements EventSubscriberInterface
{

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onException', 100],
        ];
    }

    /**
     * @param ExceptionEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function onException(ExceptionEvent $event): void
    {
        $e = $event->getException();
        // log exception
        if (!($e instanceof CHttpException) || ($e->isLoggable())) {
            CError::logException($e, false);
        }

        // init
        $status_code = $e instanceof CHttpException ? $e->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        $headers     = $e instanceof CHttpException ? $e->getHeaders() : [];

        $response = $event->getRequest()->attributes->getBoolean('is_api') ?
            $this->makeApiResponse($event, $status_code, $headers) :
            $this->makeResponse($event, $status_code, $headers);

        $event->setResponse($response);
    }

    /**
     * @param ExceptionEvent $event
     * @param string                       $status_code
     * @param array                        $headers
     *
     * @return Response
     */
    protected function makeResponse(
        ExceptionEvent $event,
        string $status_code,
        array $headers = []
    ): Response {
        $e       = $event->getException();
        $message = 'Code ' . $e->getCode() . ' : ' . $e->getMessage();

        return new Response($message, $status_code, $headers);
    }


    /**
     * @param ExceptionEvent $event
     * @param string                       $status_code
     * @param array                        $headers
     *
     * @return Response
     * @throws CApiException
     */
    protected function makeApiResponse(
        ExceptionEvent $event,
        string $status_code,
        array $headers = []
    ): Response {
        $e      = $event->getException();
        $format = (new CRequestFormats($event->getRequest()))->getExpected();
        $datas  = [
            'type'    => base64_encode(get_class($e)),
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
        ];

        $resource = new CItem($datas);
        $resource->setSerializer(CErrorSerializer::class);
        $resource->setFormat($format);

        return (new CController())->renderApiResponse($resource, $status_code, $headers);
    }

}
