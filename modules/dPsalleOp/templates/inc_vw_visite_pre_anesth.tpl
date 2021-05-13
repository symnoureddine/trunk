{{*
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $consult_anesth->_id}}
  <table class="form">
    {{if $consult_anesth->_ref_techniques|@count}}
    <tr>
      <th colspan="2" class="title">Techniques complémentaires</th>
    </tr>
    <tr>
      <td colspan="2" class="text">
        <ul>
        {{foreach from=$consult_anesth->_ref_techniques item=_technique}}
          <li>{{$_technique->technique}}</li>
        {{/foreach}}
        </ul>
      </td>
    </tr>
    {{/if}}
  </table>
  <table class="tbl">
    {{assign var=consultation value=$consult_anesth->_ref_consultation}}
    <!-- Affichage d'information complementaire pour l'anestesie -->
    <tr>
      <th class="title">Consultation préanesthésique</th>
    </tr>
    <tr>
      <td class="text">
        <button type="button" class="print" onclick="printFicheAnesth('{{$consult_anesth->_id}}')" style="float: right">
          Consulter la fiche
        </button>
        {{if $dialog}}
          {{mb_include module=mediusers template=inc_vw_mediuser mediuser=$consultation->_ref_chir}}
          -
          <span onmouseover="ObjectTooltip.createEx(this, '{{$consult_anesth->_guid}}')">
          le {{mb_value object=$consultation field="_date"}}
        </span>
        {{else}}
        <a href="?m=cabinet&tab=edit_consultation&selConsult={{$consultation->_id}}">
          {{mb_include module=mediusers template=inc_vw_mediuser mediuser=$consultation->_ref_chir}}
          -
          <span onmouseover="ObjectTooltip.createEx(this, '{{$consult_anesth->_guid}}')">
          le {{mb_value object=$consultation field="_date"}}
          </span>
        </a>
        {{/if}}
      </td>
    </tr>
  </table>
{{else}}
  {{mb_include module=cabinet template=inc_choose_dossier_anesth}}
{{/if}}

{{assign var=hide_visite value='dPsalleOp COperation hide_visite_pre_anesth'|gconf}}
{{if !$selOp->urgence || ($selOp->urgence && !$hide_visite)}}
  <div id="visite_pre_anesth">
    {{mb_include module=salleOp template=inc_visite_pre_anesth}}
  </div>
{{else}}
  <div class="small-info">
    La visite préanesthésique n'est pas nécessaire dans le cas des interventions en urgence
  </div>
{{/if}}