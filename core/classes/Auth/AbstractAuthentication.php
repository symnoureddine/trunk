<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth;

use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\CAppUI;
use Ox\Core\CModelObject;
use Ox\Mediboard\Admin\CLDAP;
use Ox\Mediboard\Admin\CUser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
abstract class AbstractAuthentication implements IAuthentication
{
    /** @var Request */
    protected $request;

    /** @var bool */
    private $is_request_api;

    /** @var string|null */
    private $fallback_method;

    /** @var string|null */
    protected $typed_password;

    final public function __construct()
    {
    }

    protected function getUser(): CUser
    {
        $user              = new CUser();
        $user->_is_logging = true;

        return $user;
    }

    public function isRequestAPI(): bool
    {
        return $this->is_request_api;
    }

    /**
     * Set the request
     *
     * @param Request $request
     *
     * @return void
     */
    public function setRequest(Request $request): void
    {
        $this->request        = $request;
        $this->is_request_api = $this->request->attributes->getBoolean('is_api');
    }

    /**
     * @inheritDoc
     */
    public function getFallBackMethod(): ?string
    {
        return $this->fallback_method;
    }

    protected function setFallbackMethod(string $method): void
    {
        $this->fallback_method = $method;
    }

    /**
     * @inheritDoc
     */
    public function getTypedPassword(): ?string
    {
        return $this->typed_password;
    }
}
