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

CCanDo::checkRead();

$motif_id    = CView::get("motif_id", "ref class|CMotif", true);
$chapitre_id = CView::get("chapitre_id", "ref class|CChapitreMotif", true);
$liste       = CView::get("liste", "str");

CView::checkin();

$motif  = new CMotif();
$motifs = $motif->loadList(null, "chapitre_id");

$chapitre = new CChapitreMotif();

/** @var CChapitreMotif[] $chapitres */
$chapitres = $chapitre->loadList(null, "nom");

foreach ($chapitres as $chap) {
  $chap->loadRefsMotifs();
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("motif_id", $motif_id);
$smarty->assign("chapitre_id", $chapitre_id);
$smarty->assign("chapitres", $chapitres);
$smarty->assign("motifs", array());

if ($liste == "motif") {
  $smarty->display("vw_list_motifs");
}
elseif ($liste == "chapitre") {
  $smarty->display("vw_list_chapitres");
}
else {
  $smarty->display("vw_motifs");
}
