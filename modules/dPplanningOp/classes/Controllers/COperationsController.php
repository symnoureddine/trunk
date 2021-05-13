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
use Ox\Core\CController;
use Ox\Mediboard\Bloc\CPlageOp;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class COperationsController
 */
class COperationsController extends CController {
  /**
   * @param CRequestApi $request_api
   * @param CPlageOp    $plage
   *
   * @return Response
   * @throws \Ox\Core\Api\Exceptions\CApiException
   * @api
   */
  public function listOperationsForPlage(CRequestApi $request_api, CPlageOp $plage): Response {
    $operations = $plage->loadRefsOperations(false);

    $resource = CCollection::createFromRequest($request_api, $operations);

    return $this->renderApiResponse($resource);
  }
}
