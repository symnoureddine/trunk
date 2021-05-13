<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth;

use Exception;
use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\OauthServer\Controllers\COAuthServerController;

/**
 * Class OAuthAuthentication
 */
class OAuthAuthentication extends AbstractAuthentication
{
    /**
     * @inheritDoc
     */
    public function getMethodName(): string
    {
        return 'oauth';
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function isCandidate(): bool
    {
        return (
            $this->isRequestAPI()
            && CModule::getActive('oauthServer')
            && COAuthServerController::isAuthenticationRequest($this->request)
            && COAuthServerController::isReady()
        );
    }

    /**
     * @inheritDoc
     */
    public function doAuth(): ?CUser
    {
        // Bearer
        if ($this->request->headers->get('authorization')) {
            try {
                $user = (new COAuthServerController())->authorize($this->request);

                if ($user) {
                    return $user;
                }
            } catch (Exception $exception) {
                throw new CouldNotAuthenticate($exception->getMessage());
            }
        }

        throw CouldNotAuthenticate::userNotFound();
    }

    /**
     * @inheritDoc
     */
    public function isLoggable(): bool
    {
        return true;
    }
}
