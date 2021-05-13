<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth;

use Exception;
use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Mediboard\Admin\CKerberosLdapIdentifier;
use Ox\Mediboard\Admin\CUser;

/**
 * Description
 */
class KerberosAuthentication extends AbstractAuthentication
{
    /** @var string|null */
    private $krb_username;

    /** @var string|null */
    private $krb_auth_type;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->krb_username  = $this->request->server->get('REMOTE_USER');
        $this->krb_auth_type = $this->request->server->get('AUTH_TYPE');
    }

    /**
     * @inheritdoc
     */
    public function getMethodName(): string
    {
        return 'sso';
    }

    /**
     * @inheritdoc
     */
    public function isCandidate(): bool
    {
        return (
            !$this->isRequestAPI()
            && $this->krb_username && ($this->krb_auth_type === 'Negotiate') && CKerberosLdapIdentifier::isReady()
        );
    }

    /**
     * @inheritdoc
     */
    public function isLoggable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function doAuth(): ?CUser
    {
        try {
            if ($krb_user = CKerberosLdapIdentifier::findUserByName($this->krb_username)) {
                return $krb_user;
            }
        } catch (Exception $e) {
            // Todo: Here is only an ORM exception
            throw CouldNotAuthenticate::userNotFound($this->krb_username);
        }

        throw CouldNotAuthenticate::userNotFound($this->krb_username);
    }
}
