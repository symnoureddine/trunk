<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Controllers;

use Exception;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Request\CRequestBulk;
use Ox\Core\CController;
use Ox\Core\Kernel\CKernel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class CBulkController
 */
class CBulkController extends CController
{

    /**
     * @param CRequestApi $request_api
     *
     * @return JsonResponse
     *
     * @throws Exception
     * @api
     */
    public function execute(CRequestApi $request_api): Response
    {
        if ($request_api->getRequest()->headers->get(CRequestBulk::HEADER_SUB_REQUEST)) {
            throw new CApiException('Unauthorized bulk operations on sub request');
        }


        $sub_requests  = (new CRequestBulk($request_api))->createRequests();
        $stopOnFailure = $request_api->getRequest()->get('stopOnFailure', false);

        $results = [];
        foreach ($sub_requests as $req_id => $reg) {
            // Handle request
            /** @var Response $response */
            $response        = CKernel::getInstance()->handle($reg, HttpKernelInterface::SUB_REQUEST);
            $reponse_content = $response->getContent();
            if (is_string($reponse_content) && is_array(json_decode($reponse_content, true)) && (json_last_error(
                    ) === JSON_ERROR_NONE)) {
                $reponse_content = json_decode($response->getContent(), true);
            } else {
                $reponse_content = utf8_encode($reponse_content);
            }

            // Build result
            $results[] = [
                'id'     => $req_id,
                'status' => $response->getStatusCode(),
                'body'   => $reponse_content,
            ];

            // Stop on failure
            if ($stopOnFailure && $response->getStatusCode() >= 400) {
                break 1;
            }
        }

        return $this->renderJsonResponse($results, 200, [], false);
    }
}
