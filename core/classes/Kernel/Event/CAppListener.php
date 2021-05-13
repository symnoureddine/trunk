<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Event;

use Exception;
use Ox\Core\CApp;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CAppListener
 */
class CAppListener implements EventSubscriberInterface
{

    /** @var CApp */
    private $app;

    /** @var bool */
    private $is_started = false;

    /**
     * CAppListener constructor.
     */
    public function __construct()
    {
        $this->app = CApp::getInstance();
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST   => ['onRequest', 100],
            KernelEvents::RESPONSE  => ['onResponse', 100],
            KernelEvents::TERMINATE => ['onTerminate', 90],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function onRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        $retour  = $this->app->start($request);

        if ($retour instanceof Response) {
            $event->setResponse($retour);

            return;
        }
        $this->is_started = true;
    }

    /**
     * @param ResponseEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function onResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->is_started) {
            $event->stopPropagation();

            return;
        }

        $request = $event->getRequest();
        $this->app->stop($request);
    }


    /**
     * @param TerminateEvent $event
     *
     * @return void
     */
    public function onTerminate(TerminateEvent $event)
    {
        if (!$this->is_started) {
            $event->stopPropagation();

            return;
        }

        $request = $event->getRequest();
        $this->app->terminate($request);
    }
}
