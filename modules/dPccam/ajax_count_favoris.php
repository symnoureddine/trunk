<?php 
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CFavoriCCAM;

CCanDo::checkAdmin();

$user_id = CView::get('user_id', 'ref class|CMediusers');
$function_id = CView::get('function_id', 'ref class|CFunctions');

CView::checkin();

$favori = new CFavoriCCAM();

if ($user_id) {
  $favori->favoris_user = $user_id;
}
elseif ($function_id) {
  $favori->favoris_function = $function_id;
}

$count = $favori->countMatchingList();

if ($user_id || $function_id) {
  $data = array('count' => $count);
}
else {
  $data = array('count' => 0);
}

CApp::json($data);