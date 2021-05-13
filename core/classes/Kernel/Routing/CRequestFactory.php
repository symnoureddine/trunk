<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Helper
 */
abstract class CRequestFactory
{
    /** @var string */
    public const PATH_API = 'api';

    /** @var string */
    public const PATH_GUI = 'gui';

    /**
     * @return Request
     */
    public static function createFromGlobals(): Request
    {
        $request = Request::createFromGlobals();
        $path    = $request->getPathInfo();

        if (strpos($path, static::PATH_GUI) === 1) {
            // GUI
            $request->attributes->add(['is_api' => false]);
        } elseif (strpos($path, static::PATH_API) === 1) {
            // API
            $request->attributes->add(['is_api' => true]);
        }

        return $request;
    }
}
