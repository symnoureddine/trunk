<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam\Controllers;

use Exception;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\CController;
use Ox\Core\CMbDT;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CCodeCCAMsController
 */
class CCodeCCAMsController extends CController {
  /**
   * @param CRequestApi $request_api
   *
   * @return Response
   * @throws Exception
   * @api
   */
  public function listCodes(CRequestApi $request_api): Response {
    $code = $request_api->getRequest()->get("code");
    $date = $request_api->getRequest()->get("date", CMbDT::date());

    $code_ccam = new CDatedCodeCCAM(null, CMbDT::date($date));

    $codes = [];

    foreach ($code_ccam->findCodes($code, $code) as $_code) {
      $_code_value = $_code["CODE"];
      $_code_ccam = CDatedCodeCCAM::get($_code_value, $date);
      if ($_code_ccam->code != "-") {
        $_code_ccam->_ref_code_ccam->_ref_infotarif = null;
        $_code_ccam->_ref_code_ccam->_ref_activites = null;
        $_code_ccam->_ref_code_ccam->_ref_incompatibilites = null;
        $_code_ccam->_ref_code_ccam->_ref_notes = null;
        $_code_ccam->_ref_code_ccam->_ref_procedures = null;
        $_code_ccam->_ref_code_ccam->_ref_extensions = null;

        $codes[$_code_value] = $_code_ccam->_ref_code_ccam;
      }
    }

    $total = count($codes);

    $resource = CCollection::createFromRequest($request_api, $codes);

    $resource->createLinksPagination($request_api->getOffset(), $request_api->getLimit(), $total);

    return $this->renderApiResponse($resource);
  }
}
