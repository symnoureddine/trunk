<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Controllers;

use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CController;
use Ox\Core\CLocalesManager;
use Ox\Core\Kernel\Exception\CHttpException;
use Ox\Core\LocalesManager;
use Ox\Core\Module\CModule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CSystemController
 */
class CSystemController extends CController
{

    /**
     * @param string $mod_name
     *
     * @return Response
     * @throws CApiException
     * @api
     */
    public function showModule(string $mod_name): Response
    {
        $module = CModule::getInstalled($mod_name);
        if ($module === null) {
            throw new CHttpException(Response::HTTP_NOT_FOUND, 'The module ' . $mod_name . ' is not installed.');
        }
        $module->registerTabs();

        $item = new CItem($module);
        $item->addAdditionalDatas(['tabs' => $module->_tabs]);

        return $this->renderApiResponse($item);
    }

    public function offline(string $message): Response
    {
        $root_dir = CAppUI::conf('root_dir');
        $path     = "./images/pictures";
        $vars     = [
            "src_logo"    => (file_exists(
                "$root_dir/$path/logo_custom.png"
            ) ? "$path/logo_custom.png" : "$path/logo.png"),
            "message"     => $message,
            "application" => CAppUI::conf("product_name"),
        ];
        $headers  = [
            "Retry-After"  => 300,
            "Content-Type" => "text/html; charset=iso-8859-1",
        ];

        return $this->renderVueResponse('offline.tpl', $vars, 503, $headers);
    }

    /**
     * @api public
     */
    public function status()
    {
        [$header, $status] = explode(':', CAPP::getProxyHeader());

        $datas    = [
            'status'  => $status,
            'version' => CApp::getVersion()['string'],
        ];
        $resource = new CItem($datas);
        $resource->setName('api_status');

        return $this->renderApiResponse($resource, 200, [$header => $status]);
    }
}
