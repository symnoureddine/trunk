<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth;

use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\Auth\Exception\DoNotIncrementLoginAttemptsException;
use Ox\Core\Auth\Traits\CredentialAuthenticationTrait;
use Ox\Core\CAppUI;
use Ox\Core\CMbException;
use Ox\Mediboard\Admin\CLDAP;
use Ox\Mediboard\Admin\CLDAPNoSourceAvailableException;
use Ox\Mediboard\Admin\CMbInvalidCredentialsException;
use Ox\Mediboard\Admin\CUser;

/**
 * Class StandardAuthentication
 */
class StandardAuthentication extends AbstractAuthentication
{
    use CredentialAuthenticationTrait;

    private const METHOD_NAME = 'standard';

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->method_name = self::METHOD_NAME;

        if (!$this->isRequestAPI() || ($this->request->getMethod() === 'POST')) {
            $this->username = $this->request->get('username');
            $this->password = $this->request->get('password');
        }
    }

    /**
     * @inheritDoc
     */
    public function isCandidate(): bool
    {
        return ($this->username && $this->password);
    }

    /**
     * @inheritDoc
     */
    public function doAuth(): ?CUser
    {
        if (!$this->username) {
            throw CouldNotAuthenticate::userNotFound();
        }

        if (!$this->password) {
            throw CouldNotAuthenticate::passwordRequired($this->username);
        }

        try {
            $user = $this->authenticateWithLDAP($this->username, $this->password);

            if ($user && $user->_id) {
                return $user;
            }
        } catch (CouldNotAuthenticate $e) {
            throw $e;
        } catch (CLDAPNoSourceAvailableException $e) {
            // No LDAP source, fallback to basic auth
            $this->method_name = self::METHOD_NAME;
        } catch (CMbInvalidCredentialsException $e) {
            // No login attempts blocking if user is LDAP-bound
            throw DoNotIncrementLoginAttemptsException::failedCombination($this->username);
        } catch (CMbException $e) {
            $this->method_name = self::METHOD_NAME;
        }

        return $this->doCredentialAuth($this->username, $this->password);
    }

    /**
     * @inheritDoc
     */
    public function isLoggable(): bool
    {
        return true;
    }
}
