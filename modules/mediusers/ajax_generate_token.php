<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CViewAccessToken;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::check();

$export       = CView::post("export", "str");
$weeks_before = CView::post("weeks_before", "num");
$weeks_after  = CView::post("weeks_after", "num");
$group        = CView::post("group", "enum list|0|1 default|0");
$details      = CView::post("details", "bool");
$anonymize    = CView::post("anonymize", "str");

$anonymize = ($anonymize) ? '1' : '0';

CView::checkin();

$user_id = CMediusers::get()->_id;

$params = array(
  "m=board",
  "raw=export_ical",
  "prat_id={$user_id}",
  "weeks_before={$weeks_before}",
  "weeks_after={$weeks_after}",
  "group={$group}",
  "details={$details}",
  "anonymize={$anonymize}",
);

if ($export) {
  foreach ($export as $_export) {
    $params[] = "export[]={$_export}";
  }
}

$ds = CSQLDataSource::get('std');

$token = new CViewAccessToken();

$where = array(
  'user_id'      => $ds->prepare('= ?', $user_id),
  'params'       => $ds->prepare('= ?', implode("\n", $params)),
  'restricted'   => "= '1'",
  'datetime_end' => 'IS NULL',
  'max_usages'   => 'IS NULL',
);

if (!$token->loadObject($where, 'datetime_start DESC')) {
  $token->user_id    = $user_id;
  $token->params     = implode("\n", $params);
  $token->restricted = '1';
}

if ($msg = $token->store()) {
  CAppUI::stepAjax($msg, UI_MSG_ERROR);
}

$smarty = new CSmartyDP();
$smarty->assign("url", $token->getUrl());
$smarty->display("vw_generated_token.tpl");