<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class CAppException
 */
class CAccessDeniedException extends CHttpException
{
    /** @var bool */
    protected $is_loggable = false;

    public function __construct($message = null, array $headers = [], $code = 0)
    {
        parent::__construct(Response::HTTP_FORBIDDEN, $message, $headers, $code);
    }
}
