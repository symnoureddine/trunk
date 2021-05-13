<?php 
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\Cache;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\OpenData\CImportConflict;

CCanDo::checkEdit();

CView::checkin();

$import_conflict = new CImportConflict();
$nb_medecins_audit = count($import_conflict->getCountConflictsByObject('CMedecin', 'import_rpps', true));
$nb_medecins = count($import_conflict->getCountConflictsByObject('CMedecin', 'import_rpps', false));
$nb_conflicts_audit = $import_conflict->countList(array('audit' => '= "1"', 'object_class' => '= "CMedecin"'));
$nb_conflicts = $import_conflict->countList(array('audit' => '= "0"', 'object_class' => '= "CMedecin"'));

$cache = new Cache('CMedecinImport', 'doublons_import', Cache::OUTER | Cache::DISTR);
$content = $cache->get();
$nb_duplicates = (is_countable($content)) ? count($content) : 0;

$smarty = new CSmartyDP();
$smarty->assign('nb_conflicts_audit', $nb_conflicts_audit);
$smarty->assign('nb_conflicts', $nb_conflicts);
$smarty->assign('nb_duplicates', $nb_duplicates);
$smarty->assign('nb_medecins_audit', $nb_medecins_audit);
$smarty->assign('nb_medecins', $nb_medecins);
$smarty->display('inc_conflict_options.tpl');
