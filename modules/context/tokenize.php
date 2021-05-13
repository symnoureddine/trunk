<?php
/**
 * @package Mediboard\Context
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admin\CViewAccessToken;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Contextual call tokenizer
 */

CCanDo::checkEdit();

// Call parameters, all parameters except token_username HAVE to be present in the call view as well
$ipp             = CView::get('ipp', 'str');
$nda             = CView::get('nda', 'str');
$nom             = CView::get('name', 'str');
$prenom          = CView::get('firstname', 'str');
$date_naiss      = CView::get('birthdate', 'str');
$date_sejour     = CView::get('admit_date', 'str');
$group_tag       = CView::get('group_tag', 'str');
$group_idex      = CView::get('group_idex', 'str');
$sejour_tag      = CView::get('sejour_tag', 'str');
$sejour_idex     = CView::get('sejour_idex', 'str');
$view            = CView::get('view', 'str notNull default|none');
$show_menu       = CView::get('show_menu', 'bool default|0');
$token_username  = CView::get('token_username', 'str');
$retourURL       = CView::get('RetourURL', 'str');
$rpps            = CView::get('rpps', 'str');
$cabinet_id      = CView::get('cabinet_id', 'str');
$ext_patient_id  = CView::get('ext_patient_id', 'str');
$context_guid    = CView::get('context_guid', 'str');
$g               = CView::get('g', 'str');
$consultation_id = CView::get('consultation_id', 'ref class|CConsultation');

CView::checkin();

$json = array(
  'token'   => null,
  'code'    => 0,
  "message" => null,
  'url_token' => null,
);
if (!$token_username && !$rpps) {
  $json["message"] = CAppUI::tr('common-error-Missing parameter: %s', 'token_username');
  CApp::json($json);
}

// Token user
$user                = new CUser();
if ($token_username) {
  $user->user_username = $token_username;
  $user->loadMatchingObjectEsc();
}

if (!$token_username && $rpps) {
  $mediuser        = new CMediusers();
  $mediuser->actif = "1";
  $mediuser->rpps  = $rpps;
  $mediuser->loadMatchingObjectEsc();
  if (!$mediuser->_id) {
    $json["message"] = CAppUI::tr('CContext-rpps-unavailable', $rpps);
    CApp::json($json);
  }
  $user = $mediuser->loadRefUser();
}

if (!$user || !$user->_id) {
  $json["message"] = CAppUI::tr('CContext-user_undefined', $token_username);
  CApp::json($json);
}

if (!($mediuser = $user->loadRefMediuser()) || !$mediuser->_id || !$mediuser->canDo()->read) {
  $json["message"] = CAppUI::tr('common-error-No permission on this object');
  CApp::json($json);
}

$token          = new CViewAccessToken();
$token->user_id = $user->_id;
$type_call = in_array($view, array("documents", "sejour", "intervention")) ? "raw" : "a";
$token->params  = "m=context\n$type_call=call";
if ($view == "get_infos") {
  $token->params  = "m=planningOp\na=get_dhe_recently_create";
}
if ($view == "get_docs") {
  $token->params = "m=planningOp\na=get_dhe_docs_recently_create";
}

foreach (CView::$params as $_name => $_value) {
  if ($_name != "token_username" && $_value) {
    $token->params .= "\n$_name=$_value";
  }
}

//$token->loadMatchingObject();

$token_lifetime = CAppUI::conf('context token_lifetime');

if (!$token_lifetime || $token_lifetime < 0) {
  $token_lifetime = (ini_get('session.gc_maxlifetime')) ? (int)(ini_get('session.gc_maxlifetime') / 60) : 10;
}

$token_lifetime = max($token_lifetime, 10);

// Token control
$token->datetime_start = min($token->datetime_start, CMbDT::dateTime());
$token->datetime_end   = max($token->datetime_end, CMbDT::dateTime("+{$token_lifetime} minutes"));
$token->purgeable      = 1;
$token->restricted     = 0;

if ($msg = $token->store()) {
  CAppUI::setMsg($msg, UI_MSG_ERROR);
  $msg = strip_tags($msg);
}

$json = array(
  'token'   => $msg ? null : $token->hash,
  'code'    => $msg ? 0 : 1,
  'message' => trim(strip_tags($msg)),
  'url_token' => $msg ? null : $token->getUrl()
);

CApp::json($json);
