<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCando;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Interop\Eai\CTransformationRule;
use Ox\Interop\Eai\CTransformationRuleSequence;
use Ox\Interop\Hl7\CHL7v2Message;

CCanDo::checkAdmin();

$sequence_id = CView::get('sequence_id', 'ref class|CTransformationRuleSequence');
CView::checkin();

$sequence = new CTransformationRuleSequence();
$sequence->load($sequence_id);

$hl7_message = new CHL7v2Message();
$content = $sequence->message_example;
/** @var CTransformationRule $_rule */
foreach ($sequence->loadRefsTransformationRules(['active' => " = '1' "]) as $_rule) {
    $hl7_message = $_rule->apply($content);
    $content = $hl7_message->data;
}

$smarty = new CSmartyDP();
$smarty->assign('sequence', $sequence);
$smarty->assign('hl7_message', $hl7_message);
$smarty->display('inc_apply_rule');
