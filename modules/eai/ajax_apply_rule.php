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

CCanDo::checkAdmin();

$rule_id = CView::get('rule_id', 'ref class|CTransformationRule');
CView::checkin();

$rule = new CTransformationRule();
$rule->load($rule_id);
$hl7_message = $rule->apply($rule->loadRefCTransformationRuleSequence()->message_example);

$smarty = new CSmartyDP();
$smarty->assign('hl7_message', $hl7_message);
$smarty->assign('rule', $rule);
$smarty->display('inc_apply_rule');
