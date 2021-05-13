<?php

/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet\Controllers;

use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CController;
use Ox\Core\CMbArray;
use Ox\Core\Kernel\Exception\CHttpException;
use Ox\Mediboard\Cabinet\CConsultation;
use Symfony\Component\HttpFoundation\Response;

class CConsultationController extends CController
{
    /**
     * @param CRequestApi $requestApi
     * @param CConsultation $consultation
     * @return Response
     * @throws CApiException
     * @api
     */
    public function updateConsultation(CRequestApi $requestApi, CConsultation $consultation): Response
    {
        // todo check permisssions

        $data = $requestApi->getContent(true, 'ISO-8859-1');
        $arrivee = CMbArray::get($data, 'arrivee');

        $consultation->arrivee = $arrivee;
        if ($msg = $consultation->store()) {
            throw new CHttpException(Response::HTTP_CONFLICT, $msg);
        }

        $item = CItem::createFromRequest($requestApi, $consultation);

        return $this->renderApiResponse($item);
    }
}
