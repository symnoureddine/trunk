<?php
/**
 * @package Mediboard\Core\Kernel
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel;

use Exception;
use Ox\Core\Kernel\Event\ListenersRegister;
use Ox\Core\Kernel\Exception\CKernelException;
use Ox\Core\Kernel\Resolver\CArgumentResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class CKernel
 */
class CKernel extends HttpKernel
{

    /** @var CKernel */
    private static $instance;

    /** @var Request */
    private $request;

    /**
     * CKernel constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        if (static::$instance) {
            throw new CKernelException(Response::HTTP_BAD_REQUEST, 'Kernel is already instantiated');
        }

        $this->request    = $request;
        $this->dispatcher = new EventDispatcher();
        ListenersRegister::addSubscribers($this->request, $this->dispatcher);

        $resolver         = new ControllerResolver();
        $requestStack     = new RequestStack();
        $argumentResolver = new CArgumentResolver();

        parent::__construct($this->dispatcher, $resolver, $requestStack, $argumentResolver);

        static::$instance = $this;
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function handleRequest(): Response
    {
        return $this->handle($this->request, HttpKernelInterface::MASTER_REQUEST, true);
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * @return Request
     */
    public function getMasterRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return CKernel
     * @throws CKernelException
     * @todo    ref with register container
     */
    public static function getInstance(): CKernel
    {
        if (static::$instance === null) {
            throw new CKernelException(Response::HTTP_BAD_REQUEST, 'Kernel is not instantiated');
        }

        return static::$instance;
    }

}
