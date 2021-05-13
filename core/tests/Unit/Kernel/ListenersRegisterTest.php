<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit;

use Ox\Core\Kernel\Event\CCorsListener;
use Ox\Core\Kernel\Event\CEtagListener;
use Ox\Core\Kernel\Event\CEventDispatcher;
use Ox\Core\Kernel\Event\ListenersRegister;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;


class ListenersRegisterTest extends UnitTestMediboard
{
    public function testAddSubscribers()
    {
        $dispatcher = new EventDispatcher();
        $req = new Request();
        ListenersRegister::addSubscribers($req, $dispatcher);

        $listeners = $dispatcher->getListeners();

        $this->assertNotEmpty($listeners);

        $this->assertFalse($this->isListenerInArray($listeners, CCorsListener::class));
        $this->assertFalse($this->isListenerInArray($listeners, CEtagListener::class));

        foreach (ListenersRegister::DEFAULT_LISTENERS as $_listener_name) {
            $this->assertTrue($this->isListenerInArray($listeners, $_listener_name));
        }
    }

    private function isListenerInArray(array $listeners, string $search_class): bool
    {
        foreach ($listeners as $_event_name => $_listeners) {
            foreach ($_listeners as [$_listener, $callable]) {
                if ($_listener instanceof $search_class) {
                    return true;
                }
            }
        }

        return false;
    }

    public function testAddSubscribersInApi(){
        $dispatcher = new EventDispatcher();
        $req = new Request();
        $req->attributes->add(['is_api' => true]);

        ListenersRegister::addSubscribers($req, $dispatcher);

        $listeners = $dispatcher->getListeners();

        $this->assertNotEmpty($listeners);

        foreach (ListenersRegister::DEFAULT_LISTENERS as $_listener_name) {
            $this->assertTrue($this->isListenerInArray($listeners, $_listener_name));
        }

        foreach (ListenersRegister::API_LISTENERS as $_listener_name) {
            $this->assertTrue($this->isListenerInArray($listeners, $_listener_name));
        }
    }
}
