<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth\Exception;

use Exception;
use Ox\Core\CAppUI;
use Ox\Mediboard\Admin\CUser;
use Throwable;

/**
 * Description
 */
class CouldNotAuthenticate extends Exception
{
    public const REDIRECT_LOGIN                              = 'login';
    public const REDIRECT_OFFLINE                            = 'offline';
    public const REDIRECT_CHANGE_PASSWORD                    = 'change_password';
    public const REDIRECT_CHANGE_PASSWORD_WITH_LIFE_DURATION = 'change_password_life_duration';

    /** @var string|null */
    private $username;

    /** @var string|null Redirection mode */
    private $redirection;

    /** @var int|null */
    private $remaining_attempts = null;

    /**
     * CouldNotAuthenticate constructor.
     *
     * @param string|null    $message
     * @param int            $code
     * @param Throwable|null $previous
     * @param string|null    $username
     * @param string|null    $redirection
     */
    public function __construct(
        ?string $message = '',
        int $code = 0,
        Throwable $previous = null,
        ?string $username = null,
        ?string $redirection = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->username    = $username;
        $this->redirection = ($redirection) ?: self::REDIRECT_LOGIN;
    }

    public function getRejectedUsername(): ?string
    {
        return $this->username;
    }

    public function getRedirection(): string
    {
        return $this->redirection;
    }

    public function setRemainingAttempts(int $attempts): void
    {
        $this->remaining_attempts = $attempts;
    }

    public function getRemainingAttempts(): ?int
    {
        return $this->remaining_attempts;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public static function userNotFound(?string $username = null): self
    {
        return new static(CAppUI::tr('User not found'), 0, null, $username);
    }

    public static function noSession(): self
    {
        return new static(CAppUI::tr('Auth-failed'));
    }

    public static function passwordRequired(?string $username = null): self
    {
        return new static(CAppUI::tr('Auth-failed-nopassword'), 0, null, $username);
    }

    public static function failedCombination(?string $username = null): self
    {
        return new static(CAppUI::tr('Auth-failed-combination'), 0, null, $username);
    }

    public static function authenticationCardMismatch(): self
    {
        return new static(CAppUI::tr('CUserAuthentication-error-Authentication card mismatch'));
    }

    public static function noConfiguredCard(): self
    {
        return new static(CAppUI::tr('CUserAuthentication-error-No configured authentication card'));
    }

    public static function invalidToken(): self
    {
        return new static(CAppUI::tr('Auth-failed-invalidToken'));
    }

    public static function userIsLocked(?string $username = null): self
    {
        return new static(CAppUI::tr('Auth-failed-user-locked'), 0, null, $username);
    }

    public static function noLDAPSourceAvailable(?string $username = null): self
    {
        return new static(CAppUI::tr('CSourceLDAP_all-unreachable'), 0, null, $username);
    }

    public static function userIsATemplate(CUser $user): self
    {
        return new static(CAppUI::tr('Auth-failed-template'), 0, null, $user->user_username);
    }

    public static function userIsSecondary(CUser $user): self
    {
        return new static(
            CAppUI::tr('CUserAuthentication-error-Connection of secondary user is not permitted.'),
            0,
            null,
            $user->user_username
        );
    }

    public static function userIsDeactivated(CUser $user): self
    {
        return new static(CAppUI::tr('Auth-failed-user-deactivated'), 0, null, $user->user_username);
    }

    public static function userHasNoRemoteAccess(CUser $user): self
    {
        return new static(CAppUI::tr('Auth-failed-user-noremoteaccess'), 0, null, $user->user_username);
    }

    public static function systemIsOfflineForNonAdmins(): self
    {
        return new static(
            'Le système est désactivé pour cause de maintenance.',
            0,
            null,
            null,
            self::REDIRECT_OFFLINE
        );
    }

    public static function userMustChangePassword(): self
    {
        return new static(
            CAppUI::tr('CAuthentication-error-User must change password'),
            0,
            null,
            null,
            self::REDIRECT_CHANGE_PASSWORD
        );
    }

    public static function userPasswordHasExpired(): self
    {
        return new static(
            CAppUI::tr('CAuthentication-error-Password has expired'),
            0,
            null,
            null,
            self::REDIRECT_CHANGE_PASSWORD_WITH_LIFE_DURATION
        );
    }

    public static function noElectedMethod(?string $message = null): self
    {
        $message = ($message) ? CAppUI::tr($message) : null;

        return new static($message, 0, null, null, self::REDIRECT_LOGIN);
    }

    public static function unknownError(?string $username = null): self
    {
        return new static(CAppUI::tr('unknown_error'), 0, null, $username);
    }
}
