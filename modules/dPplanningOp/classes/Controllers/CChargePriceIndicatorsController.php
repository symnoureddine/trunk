<?php
/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PlanningOp\Controllers;

use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CController;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\PlanningOp\CChargePriceIndicator;
use Symfony\Component\HttpFoundation\Response;

class CChargePriceIndicatorsController extends CController {
  /**
   * @param CRequestApi $request_api
   * @param string      $type
   *
   * @return Response
   * @api
   */
  public function listCharges(CRequestApi $request_api, string $type): Response {
    $group_id = $request_api->getRequest()->get('sih_group_id', CGroups::loadCurrent()->_id);
    $charge = new CChargePriceIndicator();

    $ds = $charge->getDS();

    $where = [
      "group_id" => $ds->prepare("= ?", $group_id),
      "type"     => $ds->prepare("= ?", $type)
    ];

    $charges = $charge->loadList($where, "libelle", $request_api->getLimitAsSql());

    $total = $charge->countList($where);

    $resource = CCollection::createFromRequest($request_api, $charges);
    $resource->createLinksPagination($request_api->getOffset(), $request_api->getLimit(), $total);

    return $this->renderApiResponse($resource);
  }

  /**
   * @param CRequestApi           $request_api
   * @param CChargePriceIndicator $charge
   *
   * @return Response
   * @api
   */
  public function showCharge(CRequestApi $request_api, CChargePriceIndicator $charge): Response {
    return $this->renderApiResponse(CItem::createFromRequest($request_api, $charge));
  }
}
