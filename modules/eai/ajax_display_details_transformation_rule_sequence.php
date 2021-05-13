<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;

/**
 * View transformation rules EAI
 */
CCanDo::checkAdmin();

$transformation_ruleset_id = CValue::getOrSession("transformation_ruleset_id");
$transformation_rule_sequence_id = CValue::getOrSession("transformation_rule_sequence_id");

$transf_rule_sequence = new CTransformationRuleSequence();

if ($transformation_rule_sequence_id) {
    $transf_rule_sequence->load($transformation_rule_sequence_id);
    $transf_rule_sequence->loadRefsTransformationRules();
    $transf_rule_sequence->getMessage();
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("transformation_ruleset_id", $transformation_ruleset_id);
$smarty->assign("transf_rule_sequence", $transf_rule_sequence);
$smarty->display("inc_display_details_transformation_rule_sequence.tpl");
