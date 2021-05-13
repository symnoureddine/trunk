<?php
/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Urgences\CChapitreMotif;
use Ox\Mediboard\Urgences\CMotif;
use Ox\Mediboard\Urgences\CRPU;

CCanDo::checkRead();
$rpu_id      = CView::get("rpu_id", "ref class|CRPU", true);
$chapitre_id = CView::get("chapitre_id", "ref class|CChapitreMotif", true);
$search      = CView::get("search", "str");
$reload      = CView::get("reload", "bool default|0");
CView::checkin();

$rpu = new CRPU();
$rpu->load($rpu_id);
$rpu->loadRefMotif();
$rpu->loadRefsReponses();
$rpu->orderCtes();
$ccmu_used = $rpu->ccmu ?: $rpu->_estimation_ccmu;

$where              = array();
$where["actif"]     = " = '1'";
$where["degre_min"] = " <= '$ccmu_used'";
if ($chapitre_id) {
  $where["chapitre_id"] = " = '$chapitre_id'";
}
if ($search) {
  $where[] = "nom LIKE '%$search%' or code_diag LIKE '%$search%'";
}

$motif  = new CMotif();
$motifs = $motif->loadList($where, "chapitre_id, code_diag", null, "motif_id");

$chapitre = new CChapitreMotif();
/** @var CChapitreMotif[] $chapitres */
$chapitres = $chapitre->loadList(null, "nom");

$chapitres_search = $chapitres;
foreach ($motifs as $_motif) {
  /* @var CMotif $_motif */
  $chapitres_search[$_motif->chapitre_id]->_ref_motifs[$_motif->_id] = $_motif;
}
foreach ($chapitres_search as $_chap) {
  if (!count($_chap->_ref_motifs)) {
    unset($chapitres_search[$_chap->_id]);
  }
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("rpu", $rpu);
$smarty->assign("motifs", $motifs);
$smarty->assign("search", $search);
$smarty->assign("chapitres", $chapitres);
$smarty->assign("chapitre_id", $chapitre_id);
$smarty->assign("chapitres_search", $chapitres_search);

if (!$reload) {
  $smarty->display("vw_search_motif");
}
else {
  $smarty->assign("chapitres", $chapitres_search);
  $smarty->assign("readonly", true);
  $smarty->display("vw_list_motifs");
}
