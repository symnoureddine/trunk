<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Auth\Exception;

use Ox\Core\Kernel\Exception\CHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthenticationException
 */
class AuthenticationException extends CHttpException
{
    /**
     * @inheritDoc
     */
    public function __construct(string $message = null, array $headers = [], $code = 0)
    {
        parent::__construct(Response::HTTP_UNAUTHORIZED, $message, $headers, $code);
    }
}
