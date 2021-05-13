<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


namespace Ox\Core\Kernel\Event;

use Ox\Core\CDevtools;
use OxDevtools\Devtools;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

abstract class ListenersRegister
{
    public const DEFAULT_LISTENERS = [
        CAppListener::class,
        CAuthenticationListener::class,
        CPermissionListener::class,
        CRouterListener::class,
        CControllerListener::class,
        CExceptionListener::class,
    ];

    public const API_LISTENERS = [
        CCorsListener::class,
        CEtagListener::class,
    ];

    public static function addSubscribers(Request $request, EventDispatcher $event_dispatcher): void
    {

        foreach (self::DEFAULT_LISTENERS as $_listener_class) {
            $event_dispatcher->addSubscriber(new $_listener_class());
        }

        if (class_exists(Devtools::class) && $request->headers->has(CDevtools::REQUEST_HEADER)) {
            CDevtools::init($request->headers->get(CDevtools::REQUEST_HEADER));
            $event_dispatcher->addSubscriber(new CDevtoolsListener());
        }

        if ($request->attributes->getBoolean('is_api')) {
            foreach (self::API_LISTENERS as $_listener_class) {
                $event_dispatcher->addSubscriber(new $_listener_class());
            }
        }
    }
}
