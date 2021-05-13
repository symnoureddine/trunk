<?php
/**
 * @package Mediboard\Core\OpenApi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\OpenApi;

use Exception;

/**
 * Class COpenAPIException
 */
class COpenApiException extends Exception
{

    /**
     * @param string $string sufix
     *
     * @return void
     */
    public function updateMessage($string)
    {
        $this->message .= $string;
    }
}
