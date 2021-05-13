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
use Ox\Core\Api\Request\CFilter;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Request\CRequestFilter;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CClassMap;
use Ox\Core\CController;
use Ox\Core\CModelObject;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\Kernel\Exception\CControllerException;
use Ox\Mediboard\System\CObjectClass;
use Ox\Mediboard\System\CUserAction;
use Ox\Mediboard\System\CUserLog;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CHistoryController
 */
class CHistoryController extends CController {

  /**
   * @param CRequestApi $request_api
   * @param string      $resource_name
   * @param int         $resource_id
   *
   * @return Response
   * @throws Exception
   * @api
   */
  public function list(CRequestApi $request_api, string $resource_name, int $resource_id): Response {
    $object   = $this->getObjectFromRequirements($resource_name, $resource_id);
    $resource = new CCollection($object->_ref_logs);

    return $this->renderApiResponse($resource);
  }


  /**
   * @param CRequestApi $request_api
   *
   * @param string      $resource_name
   * @param int         $resource_id
   * @param int         $history_id
   *
   * @return Response
   * @throws CApiException
   * @throws CControllerException
   * @api
   */
  public function show(CRequestApi $request_api, string $resource_name, int $resource_id, int $history_id): Response {
    $object  = $this->getObjectFromRequirements($resource_name, $resource_id);
    $history = $object->_ref_logs;

    $log_expected = null;
    foreach ($history as $log) {
      if ((int)$log->_id === (int)$history_id) {
        $log_expected = $log;
        break;
      }
    }

    if ($log_expected === null) {
      throw new CControllerException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Invalid resource identifiers.', [], 2);
    }

    if($request_api->getRequest()->query->getBoolean('loadResource')){
        /** @var CStoredObject $target */
        $target = $object->loadListByHistory($history_id);
        $resource = new CItem($target);
        $resource->setName($resource_name);
    }else{
        $resource = new CItem($log);
        $resource->setModelRelations('all');
    }

    return $this->renderApiResponse($resource);
  }

  /**
   * @param string $resource_name
   * @param int    $resource_id
   *
   * @return CStoredObject
   * @throws CControllerException|Exception
   */
  private function getObjectFromRequirements(string $resource_name, int $resource_id) {
    $object_class = CModelObject::getClassNameByResourceName($resource_name);
    /** @var CStoredObject $object */
    $object = new $object_class;
    $object->load($resource_id);

    if (!$object->_id) {
      throw new CControllerException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Invalid resource identifiers.', [], 1);
    }

    $object->loadLogs();

    return $object;
  }

  /**
   * @param CRequestApi $request_api
   *
   * @return Response
   * @throws CApiException
   * @api
   */
  private function listGenerique(CRequestApi $request_api): Response {
    /** @var CRequestFilter $request_filter */
    $request_filter = $request_api->getRequestParameter(CRequestFilter::class);

    // Map resource_name & resource_id to object_class & object_id
    $object_class = null;
    $object_id    = null;

    $new_filters = [];
    // update filters
    foreach ($request_filter as $filter_key => $filter) {

      /** @var CFilter $filter */
      if ($filter->getKey() === 'resource_name') {
        $object_class  = CClassMap::getSN(CModelObject::getClassNameByResourceName($filter->getValue()));
        $new_filters[] = new CFilter('object_class', CRequestFilter::FILTER_EQUAL, $object_class);
        $request_filter->removeFilter($filter_key, false);
      }

      if ($filter->getKey() === 'resource_id') {
        $new_filters[] = new CFilter('object_id', CRequestFilter::FILTER_EQUAL, $filter->getValue());
        $request_filter->removeFilter($filter_key, false);
      }
    }

    // add filters
    foreach ($new_filters as $new_filter) {
      /** @var CFilter $new_filter */
      $request_filter->addFilter($new_filter);
    }
    $where_log = $request_filter->getSqlFilter(CSQLDataSource::get('std'));


    foreach ($request_filter as $filter_key => $filter) {
      /** @var CFilter $filter */
      if ($filter->getKey() === 'object_class') {
        $object_class_id = CObjectClass::getID($filter->getValue());
        $request_filter->addFilter(new CFilter('object_class_id', CRequestFilter::FILTER_EQUAL, $object_class_id));
        $request_filter->removeFilter($filter_key, false);
      }
    }
    $where_action = $request_filter->getSqlFilter(CSQLDataSource::get('std'));

    // CUserLog
    $log      = new CUserLog();
    $list_log = $log->loadList($where_log, $request_api->getSortAsSql(), $request_api->getLimitAsSql());
    //CStoredObject::massLoadFwdRef($list_log, "object_id");

    // CUserAction
    $action      = new CUserAction();
    $list_action = $action->loadList($where_action, $request_api->getSortAsSql(), $request_api->getLimitAsSql());
    //CStoredObject::massLoadFwdRef($list_action, "object_id");

    // merge log & action
    foreach ($list_action as $_user_action) {
      $_user_log = new CUserLog();
      $_user_log->loadFromUserAction($_user_action);
      $list_log[$_user_log->_id] = $_user_log;
    }
    $logs = $list_log;


    $resource = CCollection::createFromRequest($request_api, $logs);
    $resource->createLinksPagination($request_api->getOffset(), $request_api->getLimit());

    return $this->renderApiResponse($resource);
  }

  /**
   * @param CRequestApi $request
   *
   * @param int         $history_id
   *
   * @return Response
   * @throws CApiException
   * @api
   */
  private function showGenerique(CRequestApi $request, $history_id): Response {
    // todo vérifier les droits sur l'objet
    if ($history_id >= CUserLog::USER_ACTION_START_AUTO_INCREMENT) {
      $log = new CUserAction();
    }
    else {
      $log = new CUserLog();
    }

    $log->load($history_id);

    $resource = CItem::createFromRequest($request, $log);

    return $this->renderApiResponse($resource);
  }

}
