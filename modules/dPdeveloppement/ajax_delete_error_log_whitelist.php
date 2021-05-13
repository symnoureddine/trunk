<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// init
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\System\CErrorLogWhiteList;

CCanDo::checkAdmin();
$id  = CView::get("id", "num");
$all = CView::get("all", "bool");
CView::checkin();

$wl = new CErrorLogWhiteList();

if ($all) {
  $ds    = $wl->getDS();
  $query = "TRUNCATE {$wl->_spec->table}";
  $ds->exec($query);
  CAppUI::displayAjaxMsg('Liste blanche vidée');
  CAppUI::callbackAjax('Control.Modal.close');
}
else {
  if (!$id) {
    trigger_error('Missing whitelist élément identifiant.');
  }
  $wl->error_log_whitelist_id = $id;
  $wl->loadMatchingObject();
  $wl->delete();
  CAppUI::displayAjaxMsg('Elément supprimé');
  CAppUI::callbackAjax('Control.Modal.refresh');
}

CApp::rip();