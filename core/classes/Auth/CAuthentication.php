<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth;

use Exception;
use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\Auth\Exception\DoNotIncrementLoginAttemptsException;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CPermission;
use Ox\Core\Module\CModule;
use Ox\Core\Sessions\CSessionHandler;
use Ox\Core\Sessions\CSessionManager;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\CPreferences;
use Ox\Mediboard\System\CUserAuthentication;
use Ox\Mediboard\System\CUserAuthenticationError;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CAuthentication
 * Todo: Check if an LDAP user is allowed to pass with a "weak" password + force_changing_password config
 */
class CAuthentication
{
    /** @var array A list of authentication services */
    public const SERVICES = [
        'oauth'    => OAuthAuthentication::class,
        'basic'    => BasicAuthentication::class,
        'token'    => TokenAuthentication::class,
        //        'cps'      => CPSAuthentication::class, // gui
        //        'kerberos' => KerberosAuthentication::class, // gui
        'login'    => LoginAuthentication::class,
        'standard' => StandardAuthentication::class,
        'session'  => SessionAuthentication::class, // last service register !important
    ];

    /** @var string[] A list of authentication services who do not need $_SESSION */
    public const SERVICES_STATELESS = [
        OAuthAuthentication::class,
        BasicAuthentication::class,
        TokenAuthentication::class,
        LoginAuthentication::class,
        StandardAuthentication::class,
    ];

    /** @var Request */
    public $request;

    /** @var string */
    public $method;

    /** @var CUser */
    public $user;

    /** @var int */
    public $previous_user_id;

    /** @var int */
    public $user_remote = 0;

    /** @var CGroups|null */
    public $user_group;

    /** @var CUserAuthentication */
    public $auth;

    /** @var IAuthentication[] */
    private $registered_services = [];

    /** @var IAuthentication|null */
    private $elected_service;

    /**
     * CAuthentication constructor.
     *
     * @param Request $request
     *
     * @throws Exception
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        // Must be initialized before trying authentication (in order to perform failure logging)
        CAppUI::isIntranet();

        $this->initServices();
    }

    /**
     * Initialize the authentication services
     *
     * @return void
     */
    private function initServices(): void
    {
        $route_security            = $this->request->attributes->get('security');
        $this->registered_services = [];

        foreach (static::SERVICES as $_service_name => $_service_class) {
            /** @var IAuthentication $_service */
            $_service = new $_service_class();
            $_service->setRequest($this->request);

            $_service->init();

            // All services are registered by default
            if ($route_security === null || in_array($_service_name, $route_security, true)) {
                $this->registered_services[$_service_name] = $_service;
            }
        }
    }

    private function isRequestApi(): bool
    {
        return $this->request->attributes->getBoolean('is_api');
    }

    /**
     * Authentication process :
     *    - Elect an authentication service
     *    - Execute the elected authentication method
     *    - Check user validity
     *    - log and set CAppUI instance
     *    - Load user perms
     *
     * @throws CouldNotAuthenticate
     */
    public function doAuth(): void
    {
        // Which service
        $this->electService();

        if (!$this->elected_service) {
            $message = ($this->isRequestApi()) ? 'Auth-failed' : null;

            throw CouldNotAuthenticate::noElectedMethod($message);
        }

        $successful_service       = null;
        $increment_login_attempts = true;

        // Trying all elected services until no AuthenticationException
        try {
            if (!$this->isElectedServiceStateless()) {
                // Init session
                (CSessionManager::get())->init();
            }

            $this->user = $this->elected_service->doAuth();

            // If no exception but no user->_id, the authentication service went wrong, we quit
            if (!$this->user || !$this->user->_id) {
                $this->user = null;

                throw CouldNotAuthenticate::unknownError($this->user->user_username);
            }
        } catch (CouldNotAuthenticate $e) {
            if ($e instanceof DoNotIncrementLoginAttemptsException) {
                $increment_login_attempts = false;
            }

            if ($increment_login_attempts) {
                $remaining = $this->incrementAttempts($e->getRejectedUsername());

                if ($remaining !== null) {
                    $e->setRemainingAttempts($remaining);
                }
            }

            $this->throwException($e);
        }

        // We are logged in legacy gui mode (session)
        if ($this->elected_service instanceof SessionAuthentication) {
            // Update session lifetime, uses the prefs
            CSessionHandler::setUserDefinedLifetime();

            $session_id = $this->elected_service->getSessionId();
            CAppUI::updateUserAuthExpirationDatetime($session_id);
        } else {
            // Authentication correct, proceeding to user validation
            $this->method = $this->elected_service->getMethodName();

            // User validity
            $this->checkUserValidity();

            // Logging
            if ($this->elected_service->isLoggable()) {
                $this->logAuth();
            }
        }
    }

    /**
     * @return bool
     */
    private function isElectedServiceStateless()
    {
        return in_array(get_class($this->elected_service), self::SERVICES_STATELESS);
    }

    /**
     * @throws Exception
     */
    public function afterAuth(): void
    {
        if (!$this->elected_service instanceof SessionAuthentication) {
            // Set CAppUI
            CAppUI::setInstance($this);

            CAppUI::$instance->weak_password =
                $this->user->checkPasswordWeakness($this->elected_service->getTypedPassword());

            CAppUI::buildPrefs();
        }

        // Show errors to admin
        ini_set("display_errors", CAppUI::pref("INFOSYSTEM"));

        // Load User Perms
        CPermission::loadUserPerms();

        CAppUI::loadCoreLocales();

        CAppUI::$user = new CMediusers();
        if (CAppUI::$user->isInstalled()) {
            CAppUI::$user->load(CAppUI::$instance->user_id);
            CAppUI::$user->getBasicInfo();
            CAppUI::$instance->_ref_user =& CAppUI::$user;

            // Offline mode for non-admins
            if (CAppUI::conf("offline_non_admin")) {
                $is_admin = false;

                if (CAppUI::$user->_id != 0) {
                    // Mediuser
                    $is_admin = CAppUI::$user->isAdmin();
                } else {
                    // User
                    $user     = CUser::find(CAppUI::$instance->user_id);
                    $is_admin = CUser::isAdminUser($user->user_type);
                }

                if (!$is_admin) {
                    if (!$this->isElectedServiceStateless() || CSessionHandler::isOpen()) {
                        // Todo: Need to destroy the session here
                        CSessionHandler::end(true);
                    }

                    throw CouldNotAuthenticate::systemIsOfflineForNonAdmins();
                }
            }

            CApp::$is_robot = CAppUI::$user->isRobot();
        }

        // Load group
        global $g;  // legacy compat
        $g = CAppUI::$instance->user_group;

        // Todo: Cannot use handlers because of CConfiguration not already built
        $disconnect_pref = CPreferences::getPref('admin_unique_session', CAppUI::$instance->user_id);

        if ($disconnect_pref && $disconnect_pref['used']) {
            CUserAuthentication::disconnectSimilarOnes(CAppUI::$instance->_ref_last_auth);
        }

        $this->user->isLDAPLinked();

        if (!$this->isRequestApi()) {
            // Credentials validity
            $this->checkAuthValidity();
        }
    }

    private function incrementAttempts(?string $username = null): ?int
    {
        $user = null;
        if ($username && $username !== '0') {
            $user = $this->getUserFromUsername($username);
        }

        if (!$user || !$user->_id) {
            return null;
        }

        if ($user->loginErrorsReady()) {
            // If the user exists, but has given a wrong password let's increment his error count
            $user->user_login_errors++;

            // Password is INVALID, user is locked (by ATTEMPTS), but lock datetime is EXPIRED so we set lock datetime to now
            if ($user->isLockedByAttempts() && !$user->isStillLockedByDatetime()) {
                $user->setLockDatetime();
            }

            $user->store();

            return max(0, CAppUI::conf('admin CUser max_login_attempts') - $user->user_login_errors);
        }

        return null;
    }

    /**
     * @return IAuthentication[]
     */
    private function electService(): ?IAuthentication
    {
        foreach ($this->registered_services as $_service_name => $_service) {
            if ($_service->isCandidate()) {
                return $this->elected_service = $_service;
            }
        }

        return null;
    }

    public function getElectedService(): ?IAuthentication
    {
        return $this->elected_service;
    }

    /**
     * Update user login errors
     *
     * @return void
     */
    private function updateUser(): void
    {
        $this->user->resetLoginErrorsCounter();
        $this->user->store();
    }

    /**
     * Check password attempt, remote access, secondary user...
     * @throws CouldNotAuthenticate
     */
    private function checkUserValidity(): void
    {
        // User template case
        if ($this->user->template) {
            throw CouldNotAuthenticate::userIsATemplate($this->user);
        }

        // User is a secondary user (user duplicate)
        if ($this->user->isSecondary()) {
            throw CouldNotAuthenticate::userIsSecondary($this->user);
        }

        $sibling                = new CUser();
        $sibling->user_username = $this->user->user_username;
        $sibling->loadMatchingObjectEsc();
        $sibling->loadRefMediuser();

        $mediuser = $sibling->_ref_mediuser;

        if ($mediuser && $mediuser->_id) {
            if (!$mediuser->actif) {
                throw CouldNotAuthenticate::userIsDeactivated($this->user);
            }

            $today = CMbDT::date();
            $deb   = $mediuser->deb_activite;
            $fin   = $mediuser->fin_activite;

            // Check if the user is in his activity period
            if (($deb && $deb > $today) || ($fin && $fin <= $today)) {
                throw CouldNotAuthenticate::userIsDeactivated($this->user);
            }
        }

        if ($sibling->isLocked()) {
            throw CouldNotAuthenticate::userIsLocked($this->user->user_username);
        }

        $this->updateUser();

        // Test if remote connection is allowed
        if (CModule::getActive('mediusers')) {
            $_mediuser = $this->user->loadRefMediuser();

            if ($_mediuser && $_mediuser->_id) {
                $this->user_remote = $_mediuser->remote;
                $this->user_group  = $_mediuser->loadRefFunction()->loadRefGroup()->_id;
            }
        }

        if (!CAppUI::isIntranet() && $this->user_remote == 1 && $this->user->user_type != 1) {
            throw CouldNotAuthenticate::userHasNoRemoteAccess($this->user);
        }
    }

    /**
     * Log user auth
     *
     * @return void
     */
    private function logAuth(): void
    {
        CAppUI::$instance->auth_method = $this->method;
        $this->auth                    = CUserAuthentication::logAuth(
            $this->user,
            $this->previous_user_id,
            $this->isElectedServiceStateless()
        );
    }

    /**
     * @return CUser
     */
    public function getUser(): ?CUser
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @throws CouldNotAuthenticate
     */
    private function throwException(CouldNotAuthenticate $exception): void
    {
        $message = $exception->getMessage();

        $method_name = ($this->elected_service !== null) ? $this->elected_service->getMethodName() : 'standard';

        // Log only if login
        $log = null;

        $rejected_username = ($exception->getRejectedUsername()) ?: '0';

        $rejected_user    = null;
        $rejected_user_id = null;
        if ($rejected_username && $rejected_username !== '0') {
            $rejected_user = $this->getUserFromUsername($rejected_username);

            if ($rejected_user && $rejected_user->_id) {
                $rejected_user_id = $rejected_user->_id;
            }
        }

        $exception->setMessage($message);

        CUserAuthenticationError::logError($rejected_username, $rejected_user_id, $method_name, $message);

        throw $exception;
    }

    private function getUserFromUsername(string $username): ?CUser
    {
        $user                = new CUser();
        $user->user_username = $username;

        if ($user->loadMatchingObjectEsc()) {
            return $user;
        }

        return null;
    }

    private function getUserIdFromUsername(string $username): ?int
    {
        $user = $this->getUserFromUsername($username);

        if ($user && $user->_id) {
            return $user->_id;
        }

        return null;
    }

    /**
     * Check authentication validity : auth two factor, change password, gdpr consent ...
     * ... and redirect with appropriate exception
     * @throws Exception
     */
    private function checkAuthValidity(): void
    {
        if ($this->mustChangePassword()) {
            throw CouldNotAuthenticate::userMustChangePassword();
        }

        if ($this->passwordHasExpired()) {
            throw CouldNotAuthenticate::userPasswordHasExpired();
        }

        // todo rgpd => moved to CPermission ?
        /*
        try {
            $rgpd_manager = new CRGPDManager($g);

            if ($rgpd_manager->isEnabledFor($user->_ref_user) && $rgpd_manager->canAskConsentFor(
                    $user->_ref_user
                ) && !$rgpd_manager->checkConsentFor($user->_ref_user)) {
                CUser::requireUserConsent();
            } else {
                //        // tabBox et inclusion du fichier demandé
                //        if ($tab !== null) {
                //          $module->showTabs();
                //        }
                //        else {
                //          $module->showAction();
                //        }
            }
        } catch (CRGPDException $e) {
            CApp::log("GDPR: {$e->getMessage()}", null, CLogger::LEVEL_DEBUG);

            //      // tabBox et inclusion du fichier demandé
            //      if ($tab !== null) {
            //        $module->showTabs();
            //      }
            //      else {
            //        $module->showAction();
            //      }
        }*/
    }

    /**
     * Public for testing purposes
     *
     * @return bool
     */
    public function hasUserAWeakPassword(): bool
    {
        return CAppUI::$instance->weak_password;
    }

    /**
     * Public for testing purposes
     *
     * @return bool
     */
    public function isUserRemote(): bool
    {
        return CAppUI::$instance->user_remote;
    }

    /**
     * @return bool
     * @internal
     */
    private function canChangePassword(): bool
    {
        $user     = $this->user;
        $mediuser = CAppUI::$user;

        // Routing
        $route                      = $this->request->attributes->get('_route');
        $route_view_change_password = ($route === 'admin_view_change_password');
        $route_change_password      = ($route === 'admin_change_password');

        // No: if super admin
        if ($mediuser->isSuperAdmin()) {
            return false;
        }

        // No: if already routing to
        if ($route_view_change_password || $route_change_password) {
            return false;
        }

        // No: if user is technically unable to change
        if (!$user->canChangePassword()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function mustChangePassword(): bool
    {
        if (!$this->canChangePassword()) {
            return false;
        }

        $mediuser = CAppUI::$user;

        // Password strength
        $weak_password = $this->hasUserAWeakPassword();
        $user_remote   = $this->isUserRemote();

        // Configs
        $apply_to_all_users = (bool)CAppUI::conf('admin CUser apply_all_users');

        // Yes: if password is weak
        if ($weak_password && (!$user_remote || $apply_to_all_users)) {
            return true;
        }

        // Yes: if user's password had been manually set to change
        if ($mediuser->mustChangePassword()) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function passwordHasExpired(): bool
    {
        if (!$this->canChangePassword()) {
            return false;
        }

        $user = $this->user;

        $periodically_force_password_changing = (bool)CAppUI::conf('admin CUser force_changing_password');
        $password_life_duration               = CAppUI::conf('admin CUser password_life_duration');

        // Yes: if periodically forced to change
        if (
            $periodically_force_password_changing
            && (CMbDT::dateTime("-{$password_life_duration}") > $user->user_password_last_change)
        ) {
            return true;
        }

        return false;
    }
}
