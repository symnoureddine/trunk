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
use Ox\Mediboard\Urgences\CMotifQuestion;

CCanDo::checkRead();
$motif_id      = CView::get("motif_id", "ref class|CMotif");
$chapitre_id   = CView::get("chapitre_id", "ref class|CChapitreMotif");
$readonly      = CView::get("readonly", "bool");
$see_questions = CView::get("see_questions", "bool default|1");
CView::checkin();

$motif = new CMotif();
if ($motif_id) {
  $motif->load($motif_id);
  $motif->loadRefChapitre();
  $motif->loadRefsQuestions($readonly);
  if ($readonly) {
    $motif->loadRefsQuestionsByGroup();
  }
}

$chapitre  = new CChapitreMotif();
$chapitres = $chapitre->loadList(null, "nom");
if ($chapitre_id) {
  $chapitre->load($chapitre_id);
  $chapitre->loadRefsMotifs();
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("motif", $motif);
$smarty->assign("motif_id", $motif_id);
$smarty->assign("chapitre", $chapitre);
$smarty->assign("chapitre_id", $chapitre_id);
$smarty->assign("chapitres", $chapitres);
$smarty->assign("question", new CMotifQuestion());
$smarty->assign("readonly", $readonly);
$smarty->assign("see_questions", $see_questions);

$smarty->display("edit_chapitre_motif");
