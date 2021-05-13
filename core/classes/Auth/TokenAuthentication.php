<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth;

use Exception;
use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\CMbString;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admin\CViewAccessToken;

/**
 * Class TokenAuthentication
 */
class TokenAuthentication extends AbstractAuthentication
{
    /** @var string */
    public const HEADER_KEY = 'X-OXAPI-KEY';

    /** @var string */
    private $token_hash;

    /**
     * @inheritDoc
     */
    public function getMethodName(): string
    {
        return 'token';
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        if ($this->isRequestAPI()) {
            $this->token_hash = $this->request->headers->get(self::HEADER_KEY);
        } else {
            $this->token_hash = $this->request->get('token');

            // Token without token= in QUERY
            if ($this->request->query->count() === 1) {
                $keys = $this->request->query->keys();

                $possibly_token_hash = array_shift($keys);

                if ($this->request->query->get($possibly_token_hash) === '') {
                    $token_hash = $this->checkPossiblyShortURLHash($possibly_token_hash);

                    if ($token_hash !== null) {
                        $this->token_hash = $possibly_token_hash;
                    }
                }
            }
        }
    }

    /**
     * Todo: Move to CViewAccessToken when merged
     */
    private function checkPossiblyShortURLHash(string $hash): ?string
    {
        if (mb_strlen($hash) < 6) {
            return null;
        }

        if (!CMbString::isBase58($hash)) {
            return null;
        }

        $token       = new CViewAccessToken();
        $token->hash = $hash;
        $token->loadMatchingObjectEsc();

        if ($token && $token->_id) {
            return $token->hash;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function isCandidate(): bool
    {
        return (bool)$this->token_hash;
    }

    /**
     * @inheritDoc
     */
    public function doAuth(): ?CUser
    {
        try {
            $token = CViewAccessToken::getByHash($this->token_hash);
        } catch (Exception $e) {
            // ORM
            throw CouldNotAuthenticate::unknownError();
        }

        if (!$token->isValid()) {
            throw CouldNotAuthenticate::invalidToken();
        }

        try {
            $token->useIt();
        } catch (Exception $e) {
            // ORM
            throw CouldNotAuthenticate::unknownError();
        }

        // Use the SESSION
        $token->applyParams();

        try {
            return CUser::findOrFail($token->user_id);
        } catch (Exception $e) {
            throw CouldNotAuthenticate::userNotFound();
        }
    }

    /**
     * @inheritDoc
     */
    public function isLoggable(): bool
    {
        return true;
    }
}
