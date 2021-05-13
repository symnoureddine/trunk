<?php

namespace Ox\Interop\Fhir\Subscriber;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Interop\Connectathon\CBlink1;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Controllers\CFHIRController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CFHIRListener implements EventSubscriberInterface, IShortNameAutoloadable
{

    /**
     * @param FilterControllerEvent $event
     *
     * @throws Exception
     */
    public function blink1Start(FilterControllerEvent $event)
    {
        CFHIRController::initBlink1();
    }

    /**
     * @param FilterControllerEvent $event
     *
     * @throws Exception
     */
    public function start(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        CFHIRController::start($request);
    }

    /**
     * @param FilterResponseEvent $event
     *
     * @throws Exception
     */
    public function blink1Stop(FilterResponseEvent $event)
    {
        $response      = $event->getResponse();
        $status_code   = $response->getStatusCode();
        $blink_pattern = null;

        if ($status_code === Response::HTTP_UNAUTHORIZED || $status_code === Response::HTTP_FORBIDDEN) {
            $blink_pattern = CFHIR::BLINK1_UNKNOW;
        } elseif ($status_code >= 200 && $status_code < 300) {
            $blink_pattern = CFHIR::BLINK1_OK;
        } elseif ($status_code >= 300 && $status_code < 400) {
            $blink_pattern = CFHIR::BLINK1_WARNING;
        } elseif ($status_code >= 400) {
            $blink_pattern = CFHIR::BLINK1_ERROR;
        }

        CBlink1::getInstance()->stopPattern($blink_pattern);
        CBlink1::getInstance()->playPattern($blink_pattern);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function contentLength(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $headers  = $response->headers;

        if (!$headers->has('Content-Length') && !$headers->has('Transfer-Encoding')) {
            $headers->set('Content-Length', strlen($response->getContent()));
        }
    }

    /**
     * @param FilterResponseEvent $event
     *
     * @throws Exception
     */
    public function cast(FilterResponseEvent $event)
    {
        $response = $event->getResponse();

        if (!in_array($response->headers->get('Content-Type'), CFHIR::getContentTypes())) {
            $e = new Exception($response->getContent(), $response->getStatusCode());
            // todo force to set new response (mathias)
            $new_response = CFHIRController::getErrorResponse($e);
            $response->setContent($new_response->getContent());
            $response->headers->set("content-type", $new_response->headers->get('Content-Type'));
        }
    }

    /**
     * @param FilterResponseEvent $event
     *
     * @throws Exception
     */
    public function stop(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        CFHIRController::stop($response);
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => [
                //array('blink1Start', 50),
                ['start', 40],
            ],
            KernelEvents::RESPONSE   => [
                //array('cast', 400),
                //array('blink1Stop', 300),
                ['contentLength', 200],
                ['stop', 100],
            ],
        ];
    }

}
