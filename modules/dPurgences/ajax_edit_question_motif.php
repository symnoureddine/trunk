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
use Ox\Mediboard\Urgences\CMotifQuestion;

CCanDo::checkRead();
$question_id = CView::get("question_id", "ref class|CMotifQuestion");
$motif_id    = CView::get("motif_id", "ref class|CMotif");
CView::checkin();

$question = new CMotifQuestion();
$question->load($question_id);

if ($question_id) {
  $question->load($question_id);
}
else {
  $question->motif_id = $motif_id;
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("question", $question);

$smarty->display("edit_question_motif");