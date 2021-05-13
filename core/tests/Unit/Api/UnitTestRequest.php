<?php

/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;

/**
 * Description
 */
abstract class UnitTestRequest extends UnitTestMediboard
{
    /** @var array */
    protected $listeners = [];

    protected function makeKernel(): HttpKernel
    {
        $dispatcher = new EventDispatcher();

        foreach ($this->listeners as $_listener) {
            $dispatcher->addSubscriber($_listener);
        }

        $resolver     = new ControllerResolver();
        $requestStack = new RequestStack();

        return new HttpKernel($dispatcher, $resolver, $requestStack);
    }

    protected function addListener(EventSubscriberInterface $listener)
    {
        $this->listeners[] = $listener;
    }

    protected function handleRequest($request)
    {
        $kernel = $this->makeKernel();

        return $kernel->handle($request);
    }
}
