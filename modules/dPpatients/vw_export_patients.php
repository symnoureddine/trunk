<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkAdmin();

$praticien_id         = CView::post("praticien_id", "str", true);
$step                 = CView::post("step", "num default|100", true);
$start                = CView::post("start", "num default|0", true);
$directory            = CView::post("directory", "str", true);
$all_prats            = CView::post("all_prats", "str", true);
$ignore_files         = CView::post("ignore_files", "str", true);
$generate_pdfpreviews = CView::post("generate_pdfpreviews", "str", true);
$date_min             = CView::post("date_min", "date", true);
$date_max             = CView::post("date_max", "date", true);
$patient_id           = CView::post("patient_id", "ref class|CPatient", true);
$use_function         = CView::post("use_function", "str", true);
$patient_infos        = CView::post("patient_infos", "str", true);
$update               = CView::post("update", "str", true);

CView::checkin();

$praticien = new CMediusers();
// load all the users from the group
$praticiens = $praticien->loadListFromType(null, PERM_READ, null, null, false);

if (!$praticien_id) {
  $praticien_id = array();
}

$smarty = new CSmartyDP();
$smarty->assign("praticiens", $praticiens);
$smarty->assign("group", CGroups::loadCurrent());
$smarty->assign("praticien_id", $praticien_id);
$smarty->assign("all_prats", $all_prats);
$smarty->assign("step", $step);
$smarty->assign("start", $start);
$smarty->assign("directory", $directory);
$smarty->assign("ignore_files", $ignore_files);
$smarty->assign("generate_pdfpreviews", $generate_pdfpreviews);
$smarty->assign("date_min", $date_min);
$smarty->assign("date_max", $date_max);
$smarty->assign("patient_id", $patient_id);
$smarty->assign("use_function", $use_function);
$smarty->assign("patient_infos", $patient_infos);
$smarty->assign("update", $update);
$smarty->display("vw_export_patients.tpl");
