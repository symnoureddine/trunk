<?php
/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PlanningOp\Controllers;


use Exception;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CController;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\CProtocole;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CProtocolesController
 */
class CProtocolesController extends CController
{
    /**
     * @param CRequestApi $request_api
     *
     * @return Response
     * @throws Exception
     * @api
     */
    public function listProtocoles(CRequestApi $request_api): Response
    {
        $libelle    = utf8_decode($request_api->getRequest()->get("libelle"));
        $chir_id    = $request_api->getRequest()->get("chir_id", CMediusers::get()->_id);
        $for_sejour = $request_api->getRequest()->get('for_sejour', 0);

        $protocole = new CProtocole();
        $ds        = $protocole->getDS();

        $where = [
            'libelle' . ($for_sejour ? '_sejour' : null) => $ds->prepareLike("$libelle%"),
        ];

        $where[] = "chir_id " . $ds->prepare("= ?", $chir_id)
            . " OR function_id " . $ds->prepare("= ?", CMediusers::get($chir_id)->function_id)
            . " OR group_id " . $ds->prepare("= ?", CGroups::loadCurrent()->_id);

        if ($for_sejour) {
            $where['for_sejour'] = "= '1'";
        }

        $protocoles = $protocole->loadList($where, $request_api->getSortAsSql(), $request_api->getLimitAsSql());

        $total = $protocole->countList($where);

        $resource = CCollection::createFromRequest($request_api, $protocoles);
        $resource->createLinksPagination($request_api->getOffset(), $request_api->getLimit(), $total);

        return $this->renderApiResponse($resource);
    }

    /**
     * @param CRequestApi $request_api
     * @param CProtocole  $protocole
     *
     * @return Response
     * @throws CApiException
     * @api
     */
    public function showProtocole(CRequestApi $request_api, CProtocole $protocole): Response
    {
        return $this->renderApiResponse(CItem::createFromRequest($request_api, $protocole));
    }
}
