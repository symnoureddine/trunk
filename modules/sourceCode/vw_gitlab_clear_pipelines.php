<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use GuzzleHttp\Client;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Api\CGitLabApiClient;

/**
 * @warning Use this script only in dev-time
 */

CCanDo::checkAdmin();
$interval = CView::get('interval', 'str', 'P1M');
$debug = CView::get('debug', 'bool', null);
CView::checkin();

// token
if(!$token = CAppUI::conf('dPdeveloppement gitlab_api_token')){
  throw new Exception('Missing authentication token');
}

$client = new Client();
$gitlab = new CGitLabApiClient($client, $token, CGitLabApiClient::MEDIBOARD_PROJECT_ID, $debug);

try {
  $gitlab->clearOldPipeline($interval);
}
catch (Exception $e) {
  dump($e);
}