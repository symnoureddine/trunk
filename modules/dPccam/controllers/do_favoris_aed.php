<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CDoObjectAddEdit;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * CCAM
 */
$do = new CDoObjectAddEdit("CFavoriCCAM", "favoris_id");

CView::checkin();

// Amélioration des textes
if ($favori_user = CView::post("favoris_user", 'ref class|CUser')) {
  $user = new CMediusers;
  $user->load($favori_user);
  $for = " pour $user->_view";
  $do->createMsg .= $for;
  $do->modifyMsg .= $for;
  $do->deleteMsg .= $for;
}
elseif ($favori_function = CView::post('favoris_function', 'ref class|CFunctions')) {
  $function = new CFunctions();
  $function->load($favori_function);
  $for = " pour $function->_view";
  $do->createMsg .= $for;
  $do->modifyMsg .= $for;
  $do->deleteMsg .= $for;
}

$do->redirect = null;

$do->doIt();

if (CAppUI::pref("new_search_ccam") == 1) {
  echo CAppUI::getMsg();
  CApp::rip();
}
