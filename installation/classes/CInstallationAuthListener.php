<?php
/**
 * @package Ox\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Installation;

use Exception;
use Ox\Installation\Controllers\CInstallationController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CAuthenticationListener
 */
class CInstallationAuthListener implements EventSubscriberInterface
{
    const PUBLIC_ROUTES = [
        'installation_home',
    ];

    /**
     * @param GetResponseEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function doAuth(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        // home page
        if (in_array($request->get('_route'), self::PUBLIC_ROUTES)) {
            return;
        }

        // private routes
        CInstallationController::doAuth($request);
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['doAuth', 20]],
        ];
    }

}
