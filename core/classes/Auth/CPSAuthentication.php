<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth;

use Exception;
use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\CMbSecurity;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Files\CFile;

/**
 * Todo: Not tested!
 */
class CPSAuthentication extends AbstractAuthentication
{
    /** @var string */
    private $signature;

    /** @var string */
    private $certificat_signature;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->signature            = $this->request->get('signature');
        $this->certificat_signature = $this->request->get('certificat_signature');
    }

    /**
     * @inheritDoc
     */
    public function getMethodName(): string
    {
        return 'card';
    }

    /**
     * @inheritDoc
     */
    public function isCandidate(): bool
    {
        return (!$this->isRequestAPI() && $this->signature && $this->certificat_signature);
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
        $file               = new CFile();
        $file->file_name    = 'certificat_signature.crt';
        $file->object_class = 'CMediusers';
        $file->private      = 1;
        $files              = $file->loadMatchingList();

        $md5_certificat = md5($this->certificat_signature);
        $user_id        = null;

        /** @var CFile $_file */
        foreach ($files as $_file) {
            if ($md5_certificat === md5(file_get_contents($_file->_file_path))) {
                $user_id = $_file->object_id;
                break;
            }
        }

        if (!$user_id) {
            throw CouldNotAuthenticate::authenticationCardMismatch();
        }

        $file->object_id = $user_id;
        $file->file_name = 'certificat_auth.crt';
        $file->loadMatchingObject();

        if (!$file->_id) {
            throw CouldNotAuthenticate::noConfiguredCard();
        }

        $cipher = CMbSecurity::getCipher(CMbSecurity::RSA);

        $is_verified = CMbSecurity::verify(
            $cipher,
            'sha1',
            2,
            $this->certificat_signature,
            'mediboard',
            base64_decode($this->signature)
        );

        if (!$is_verified) {
            throw CouldNotAuthenticate::unknownError();
        }

        try {
            return CUser::findOrFail($user_id);
        } catch (Exception $e) {
            throw CouldNotAuthenticate::userNotFound();
        }
    }
}
