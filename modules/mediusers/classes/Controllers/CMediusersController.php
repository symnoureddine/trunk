<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Mediusers\Controllers;

use Exception;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CController;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CMediusersController
 */
class CMediusersController extends CController {
  /**
   * @param CRequestApi $request_api
   *
   * @return Response
   * @throws Exception
   * @api
   */
  public function listMediusers(CRequestApi $request_api): Response {
    $type = $request_api->getRequest()->get("type", "prat");
    $name = $request_api->getRequest()->get("name");

    switch ($type) {
      case "prat":
      default:
        $mediusers = (new CMediusers())->loadPraticiens(PERM_READ, null, $name, false, true, true, CGroups::loadCurrent()->_id);
        break;

      case "anesth":
        $mediusers = (new CMediusers())->loadAnesthesistes(PERM_READ, null, $name);
    }

    $total = count($mediusers);

    $resource = CCollection::createFromRequest($request_api, $mediusers);

    $resource->createLinksPagination($request_api->getOffset(), $request_api->getLimit(), $total);

    return $this->renderApiResponse($resource);
  }

  /**
   * @param CRequestApi $request_api
   * @param CMediusers  $mediuser
   *
   * @return Response
   * @throws Exception
   * @api
   */
  public function showMediuser(CRequestApi $request_api, CMediusers $mediuser): Response {
    $mediuser->loadRefFunction();
    return $this->renderApiResponse(CItem::createFromRequest($request_api, $mediuser));
  }

  /**
   * @param CRequestApi $request_api
   *
   * @return Response
   * @throws Exception
   * @api
   */
  public function showMediuserByRPPS(CRequestApi $request_api): Response {
    $mediuser = new CMediusers();
    $mediuser->rpps = $request_api->getRequest()->get("rpps");
    $mediuser->loadMatchingObject();
    $mediuser->loadRefFunction();
    return $this->renderApiResponse(CItem::createFromRequest($request_api, $mediuser));
  }
}