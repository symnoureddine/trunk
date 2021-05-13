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
use Ox\Mediboard\Files\CFilesCategory;
use Ox\Mediboard\Patients\CPatient;

CCanDo::checkRead();

$patient_id   = CView::getRefCheckRead("patient_id", "ref class|CPatient");
$context_guid = CView::get("context_guid", "str default|CPatient-$patient_id");
$tri          = CView::get("tri", "enum list|date|context|cat default|date");
$display      = CView::get("display", "enum list|icon|list default|" . CAppUI::pref("display_all_docs"));
$type_doc     = CView::get("type_doc", "str default|all");
$ondblclick   = CView::get("ondblclick", "str");

CView::checkin();

$patient = new CPatient();
$patient->load($patient_id);

[$context_class, $context_id] = explode("-", $context_guid);

$smarty = new CSmartyDP();

$smarty->assign("patient_id", $patient_id);
$smarty->assign("patient", $patient);
$smarty->assign("context_guid", $context_guid);
$smarty->assign("context_class", $context_class);
$smarty->assign("context_id", $context_id);
$smarty->assign("categories", CFilesCategory::listCatClass());
$smarty->assign("display", $display);
$smarty->assign("tri", $tri);
$smarty->assign("type_doc", $type_doc);
$smarty->assign("ondblclick", $ondblclick);

$smarty->display("vw_all_docs.tpl");
