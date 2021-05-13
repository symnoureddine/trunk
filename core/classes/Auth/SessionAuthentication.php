<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth;

use Exception;
use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\CAppUI;
use Ox\Core\Sessions\CSessionManager;
use Ox\Mediboard\Admin\CUser;

/**
 * Class SessionAuthentication
 */
class SessionAuthentication extends AbstractAuthentication
{
    /** @var string */
    private $session_name;

    /** @var string */
    private $session_cookie;

    /**
     * @inheritDoc
     */
    public function getMethodName(): string
    {
        return 'standard';
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->session_name   = CAppUI::forgeSessionName();
        $this->session_cookie = $this->request->cookies->get($this->session_name);
    }

    /**
     * @inheritDoc
     */
    public function isCandidate(): bool
    {
        return (($this->session_name !== null) && ($this->session_cookie !== null));
    }

    /**
     * @return bool|int $user_id if is a valid session, false if an error occurred
     */
    private function getUserIdFromSession()
    {
        // todo check if CSessionListener is registered ?

        // Check if session has previously been initialised
        if (!CAppUI::$instance instanceof CAppUI) {
            return false;
        }

        // Check if we have an user_id
        if (CAppUI::$instance->user_id === null) {
            return false;
        }

        if (CAppUI::$instance->user_id === 0) {
            return false;
        }

        return CAppUI::$instance->user_id;
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return session_id();
    }

    /**
     * @inheritDoc
     */
    public function doAuth(): ?CUser
    {
        $user_id = $this->getUserIdFromSession();

        if (!$user_id) {
            throw CouldNotAuthenticate::noSession();
        }

        try {
            return CUser::findOrFail($user_id);
        } catch (Exception $e) {
            throw CouldNotAuthenticate::userNotFound();
        }
    }

    /**
     * @inheritDoc
     */
    public function isLoggable(): bool
    {
        return false;
    }
}
