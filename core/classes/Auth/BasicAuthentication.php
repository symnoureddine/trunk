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
use Ox\Core\CMbException;
use Ox\Mediboard\Admin\CLDAPNoSourceAvailableException;
use Ox\Mediboard\Admin\CMbInvalidCredentialsException;
use Ox\Mediboard\Admin\CUser;

/**
 * Class BasicAuthentication
 */
class BasicAuthentication extends AbstractAuthentication
{
    use CredentialAuthenticationTrait;

    /** @var string */
    public const METHOD_NAME = 'basic';

    /** @var string */
    private $basic;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->method_name = self::METHOD_NAME;
        $this->basic       = $this->request->headers->get('Authorization');
    }

    /**
     * @inheritDoc
     */
    public function isCandidate(): bool
    {
        return ($this->basic && strpos($this->basic, 'Basic') === 0);
    }

    /**
     * @inheritDoc
     */
    public function isLoggable(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function doAuth(): ?CUser
    {
        $b64         = explode(' ', $this->basic)[1];
        $credentials = explode(':', base64_decode($b64));

        $username = $credentials[0];
        $password = $credentials[1];

        try {
            $user = $this->authenticateWithLDAP($username, $password);

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
            throw DoNotIncrementLoginAttemptsException::failedCombination($username);
        } catch (CMbException $e) {
            $this->method_name = self::METHOD_NAME;
        }

        return $this->doCredentialAuth($username, $password);
    }
}
