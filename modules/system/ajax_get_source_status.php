<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CView;
use Ox\Mediboard\System\CExchangeSource;

/**
 * Status exchange source
 */
CCanDo::check();

$source_guid = CView::get("source_guid", "guid class|CExchangeSource");
CView::checkin();

$status = null;

$source = new CExchangeSource();
/** @var CExchangeSource $source */
if ($source_guid) {
  $source = CMbObject::loadFromGuid($source_guid);
  $source->isReachable();
  $source->getResponseTime();
}

$status = array(
  "active"        => $source->active,
  "reachable"     => $source->_reachable,
  "message"       => $source->_message,
  "name"          => $source->name,
  "response_time" => $source->_response_time > 0 ? $source->_response_time . " ms" : $source->_response_time,
);

CApp::json($status);
