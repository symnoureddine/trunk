<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\CSpecCPAM;
use Ox\Mediboard\OpenData\CImportConflict;
use Ox\Mediboard\Patients\CMedecin;

CCanDo::checkEdit();

$medecin_id   = CView::get("medecin_id", "ref class|CMedecin");
$duplicate    = CView::get("duplicate", "bool default|0");
$medecin_type = CView::get("medecin_type", "str");

CView::checkin();

$medecin = new CMedecin();
$medecin->load($medecin_id);

if ($duplicate) {
  $medecin->_id = null;
}

if ($medecin->_id) {
  $current_user = CMediusers::get();
  $is_admin = $current_user->isAdmin();
  if (CAppUI::isCabinet()) {
    $same_function = $current_user->function_id == $medecin->function_id;
    if (!$is_admin && !$same_function) {
      CAppUI::accessDenied();
    }
  }
  elseif (CAppUI::isGroup()) {
    $same_group = $current_user->loadRefFunction()->group_id == $medecin->group_id;
    if (!$is_admin && !$same_group) {
      CAppUI::accessDenied();
    }
  }
}
if (!$medecin->_id && !$medecin->type) {
  $medecin->type = $medecin_type;
}

$medecin->loadSalutations();
$medecin->loadRefsNotes();
$medecin->loadRefUser();

$conflicts = array();
if ($medecin->_id && class_exists(CImportConflict::class)) {
  $conflicts = CImportConflict::getConflictsForMedecin($medecin->_id, "import_rpps");
}

$smarty = new CSmartyDP();
$smarty->assign("object", $medecin);
$smarty->assign("spec_cpam", CSpecCPAM::getList());
$smarty->assign("conflicts", $conflicts);
$smarty->display("inc_edit_medecin.tpl");
