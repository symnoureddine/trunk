<?php
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\OpenData\CImportConflict;
use Ox\Mediboard\Patients\CMedecin;

CCanDo::checkRead();

$start      = CView::get('start', 'num default|0');
$step       = CView::get('step', 'num default|20');
$audit      = CView::get('audit', 'bool');
$nom        = CView::get('nom', 'str');
$prenom     = CView::get('prenom', 'str');
$cp         = CView::get('cp', 'str');
$medecin_id = CView::get('medecin_id', 'str');

CView::checkin();

$change_page = true;

$ids = array();
if ($medecin_id) {
  $ids         = explode('|', $medecin_id);
  $step        = 100;
  $change_page = false;
}

$import_conflict = new CImportConflict();
$conflicts = CImportConflict::getConflictsGroupByMedecin($nom, $prenom, $cp, $ids, $start, $step, $audit);
$total = count($import_conflict->getCountConflictsByObject('CMedecin', 'import_rpps', false));

$medecins_ids = array_keys($conflicts);
$fields       = array();
foreach ($conflicts as $_id => $_conflict) {
  $fields[$_id] = implode('|', CMbArray::pluck($_conflict, 'field'));
}

$medecin  = new CMedecin();
$medecins = $medecin->loadAll($medecins_ids, 'nom ASC, prenom ASC');

$perm = CModule::getCanDo('dPpatients');

$smarty = new CSmartyDP();
$smarty->assign('audit', $audit);
$smarty->assign('start', $start);
$smarty->assign('step', $step);
$smarty->assign('total', $total);
$smarty->assign('conflicts', $conflicts);
$smarty->assign('medecins', $medecins);
$smarty->assign('fields', $fields);
$smarty->assign('medecins_ids', implode('|', $medecins_ids));
$smarty->assign('change_page', $change_page);
$smarty->assign('perm', $perm);
$smarty->display('inc_search_conflict.tpl');
