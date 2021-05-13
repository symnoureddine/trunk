<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Event;

use Ox\Core\CPermission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CPermissionListener
 */
class CPermissionListener implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onController', 90],
        ];
    }

    /**
     * @param ControllerEvent $event
     *
     * @return void
     */
    public function onController(ControllerEvent $event): void
    {
        $controller = $event->getController()[0];

        // Check permission
        $permission = new CPermission($controller, $event->getRequest());
        $permission->check();
    }

}
