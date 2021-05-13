<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Event;

use Exception;
use Ox\Core\Auth\CAuthentication;
use Ox\Core\Auth\Exception\AuthenticationException;
use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\CAppUI;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CAuthenticationListener
 */
class CAuthenticationListener implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 20],
        ];
    }

    /**
     * Do authentication
     *
     * @param RequestEvent $event
     *
     * @return void
     * @throws AuthenticationException
     * @throws Exception
     */
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        // request
        $request = $event->getRequest();

        // public routes
        if ($request->attributes->get('security') === []) {
            if (CAppUI::conf('offline_non_admin')) {
                throw CouldNotAuthenticate::systemIsOfflineForNonAdmins();
            }

            return;
        }

        // private routes
        $auth = new CAuthentication($request);
        try {
            // Auth & logging
            $auth->doAuth();

            // Set CAppUI, Prefs
            $auth->afterAuth();
        } catch (CouldNotAuthenticate $e) {
            if ($request->attributes->getBoolean('is_api')) {
                // return error formatted
                throw new AuthenticationException($e->getMessage());
            }
            // todo gui
        }
    }
}
