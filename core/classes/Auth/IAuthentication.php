<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth;

use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Mediboard\Admin\CUser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface IAuthentication
 */
interface IAuthentication
{
    /**
     * Set the request
     *
     * @param Request $request
     *
     * @return void
     */
    public function setRequest(Request $request): void;

    /**
     * Initialize the needed parameters
     *
     * @return void
     */
    public function init(): void;

    /**
     * Get the authentication method name
     *
     * @return string
     */
    public function getMethodName(): string;

    /**
     * Get the fallback authentication method name
     *
     * @return array
     */
    public function getFallbackMethod(): ?string;

    /**
     * Check if authentication service can be performed
     *
     * @return bool
     */
    public function isCandidate(): bool;

    /**
     * Check if authentication service is loggable
     *
     * @return bool
     */
    public function isLoggable(): bool;

    /**
     * Try to authenticate
     *
     * @return CUser|null
     * @throws CouldNotAuthenticate
     */
    public function doAuth(): ?CUser;

    /**
     * @return string|null
     */
    public function getTypedPassword(): ?string;
}
