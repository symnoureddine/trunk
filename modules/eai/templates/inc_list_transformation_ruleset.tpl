{{*
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{foreach from=$transf_rulesets item=_transformation_ruleset}}
  <tr>
    <th class="category">
      {{mb_value object=$_transformation_ruleset field="name"}}
    </th>
    <th class="category narrow">
      <button class="button edit notext compact" onclick="EAITransformationRuleSet.edit('{{$_transformation_ruleset->_id}}');">
        {{tr}}CTransformationRuleSet-title-edit{{/tr}}
      </button>
    </th>
  </tr>
  <tr>
    <td colspan="2" class="text compact">
      {{mb_value object=$_transformation_ruleset field="description"}}
    </td>
  </tr>
  <tr>
    <th class="section">{{tr}}CTransformationRuleSequence{{/tr}}</th>
    <th class="section narrow">
      <button class="button new notext compact"
              onclick="EAITransformationRuleSequence.edit('{{$_transformation_ruleset->_id}}',null);">
        {{tr}}CTransformationRuleSequence-title-create{{/tr}}
      </button>
    </th>
  </tr>

  {{foreach from=$_transformation_ruleset->_ref_transformation_rule_sequences item=_transformation_rule_sequence}}
    <tr>
      <td colspan="2" class="text compact">
        <a href="#" onclick="EAITransformationRuleSequence.displayDetails(
            '{{$_transformation_ruleset->_id}}','{{$_transformation_rule_sequence->_id}}');">
          {{mb_value object=$_transformation_rule_sequence field="name"}}
        </a>
      </td>
    </tr>
  {{foreachelse}}
    <tr>
      <td class="empty" colspan="2">{{tr}}CTransformationRuleSequence.none{{/tr}}</td>
    </tr>
  {{/foreach}}
{{foreachelse}}
  <tr>
    <td class="empty" colspan="2">{{tr}}CTransformationRuleSet.none{{/tr}}</td>
  </tr>
{{/foreach}}