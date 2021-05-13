<?php

/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth\Traits;

use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\CAppUI;
use Ox\Mediboard\Admin\CLDAP;
use Ox\Mediboard\Admin\CUser;

/**
 * Description
 */
trait CredentialAuthenticationTrait
{
    /** @var string */
    private $method_name;

    /** @var string|null */
    protected $typed_password;

    /**
     * @inheritDoc
     */
    public function getMethodName(): string
    {
        return $this->method_name;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return CUser|null
     * @throws CouldNotAuthenticate
     */
    public function doCredentialAuth(string $username, string $password): ?CUser
    {
        $user = $this->getUser();

        $user->user_username  = $username;
        $user->_user_password = $password;

        // Use the session
        $user->preparePassword();

        if ($user->loadMatchingObjectEsc()) {
            $this->setTypedPassword($password);

            return $user;
        }

        throw CouldNotAuthenticate::failedCombination($username);
    }

    protected function setTypedPassword(string $password): void
    {
        $this->typed_password = $password;
    }

    protected function authenticateWithLDAP(string $username, string $password): ?CUser
    {
        $ldap_connection = CAppUI::conf('admin LDAP ldap_connection');
        $ldap_tag        = CAppUI::conf('admin LDAP ldap_tag');

        if (!$ldap_connection || !$ldap_tag) {
            return null;
        }

        $user_ldap                = new CUser();
        $user_ldap->user_username = $username;
        $user_ldap->loadMatchingObjectEsc();

        $idex = $user_ldap->loadLastId400($ldap_tag);

        if (!$idex || !$idex->_id) {
            return null;
        }

        $this->method_name = 'ldap';

        // The user in linked to the LDAP
        $ldap_guid                 = $idex->id400;
        $user_ldap->_user_password = $password;
        $user_ldap->_bound         = false;

        $user = null;

        $user = CLDAP::login($user_ldap, $ldap_guid);

        if (!$user->_bound) {
            throw CouldNotAuthenticate::failedCombination($username);
        }

        $this->setTypedPassword($password);

        // Set in CLDAP::login
        if ($user->_ldap_expired) {
            // Todo: Actually use the SESSION
            CUser::setPasswordMustChange();

            // Todo: Directly use CAppUI?
            CAppUI::$instance->_renew_ldap_pwd = true;
        }

        return $user;
    }
}
