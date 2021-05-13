{{*
 * @package Mediboard\Pmsi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=sejour value=$_relance->_ref_sejour}}
{{assign var=patient value=$_relance->_ref_patient}}
{{assign var=chir value=$_relance->_ref_chir}}
<tr>
  <td class="not-printable">
    <button type="button" class="edit notext"
            onclick="Relance.edit('{{$_relance->_id}}', null, Relance.searchRelances);">{{tr}}pmsi-edit_relance{{/tr}}
    </button>
  </td>
  <td>
    {{$sejour->_NDA}}
  </td>
  <td>
        <span onmouseover="ObjectTooltip.createEx(this, '{{$patient->_guid}}');">
          {{$patient}}
        </span>
  </td>
  <td>
        <span onmouseover="ObjectTooltip.createEx(this, '{{$sejour->_guid}}');">
          {{mb_value object=$sejour field=entree}}
        </span>
  </td>
  <td>
        <span onmouseover="ObjectTooltip.createEx(this, '{{$sejour->_guid}}');">
          {{mb_value object=$sejour field=sortie}}
        </span>
  </td>
  <td>
    {{if $sejour->sortie_reelle}}
      {{tr}}common-Completed-court{{/tr}}
    {{else}}
      {{tr}}common-In progress{{/tr}}
    {{/if}}
  </td>
  <td class="text">
    {{mb_include module=mediusers template=inc_vw_mediuser mediuser=$chir}}
  </td>
  <td>
    {{if $_relance->datetime_cloture}}
      {{tr}}common-Closed|f{{/tr}}
    {{elseif $_relance->datetime_relance}}
      {{tr}}pmsi-2_relance{{/tr}}
    {{else}}
      {{tr}}config-dPpmsi-relances{{/tr}}
    {{/if}}
  </td>
  {{foreach from='Ox\Mediboard\Pmsi\CRelancePMSI'|static:"docs" item=doc}}
    {{if "dPpmsi relances $doc"|gconf}}
      <td style="text-align: center;">
        {{if $_relance->$doc}}
          <span {{if $doc == "autre"}}title="{{$_relance->description}}" style="cursor: pointer;"{{/if}}>
                X
              </span>
        {{/if}}
      </td>
    {{/if}}
  {{/foreach}}
  <td class="text">
    {{mb_value object=$_relance field=commentaire_dim}}
  </td>
  <td class="text">
    {{mb_value object=$_relance field=commentaire_med}}
  </td>
  <td>
    {{mb_value object=$_relance field=urgence}}
  </td>
</tr>
