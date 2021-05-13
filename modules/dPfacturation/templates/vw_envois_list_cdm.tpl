{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="tbl">
  {{if $total_envois > 25}}
    {{mb_include module=system template=inc_pagination total=$total_envois current=$page step=25 change_page='changePageCDM'}}
  {{/if}}
  <tr>
    <th colspan="6" class="title">
      {{tr}}CEnvoiCDM{{/tr}}
      {{if $facture}}
        {{tr}}CFacture-for{{/tr}}
        {{mb_include module=system template=inc_vw_mbobject object=$facture}}
      {{/if}}
      ({{$envois_cdm|@count}})
    </th>
  </tr>

  <tr>
    <th class="narrow">{{mb_title class=CEnvoiCDM field=date}}</th>
    <th class="narrow">{{mb_title class=CEnvoiCDM field=filename}}</th>
    {{if !$facture}}
      <th class="narrow">{{mb_title class=CEnvoiCDM field=object_id}}</th>
    {{/if}}
    <th>{{mb_title class=CEnvoiCDM field=statut}}</th>
    <th>{{mb_title class=CEnvoiCDM field=result}}</th>
    <th>{{mb_title class=CEnvoiCDMMessage field=message}}</th>
  </tr>
  {{foreach from=$envois_cdm item=_envoi_cdm}}
    <tr>
      <td>{{mb_value object=$_envoi_cdm field=date}}</td>
      <td>{{mb_value object=$_envoi_cdm field=filename}}</td>
      {{if !$facture}}
        <td>
          {{mb_include module=system template=inc_vw_mbobject object=$_envoi_cdm->_ref_object}}
        </td>
      {{/if}}
      <td style="text-align: center;">
        {{mb_value object=$_envoi_cdm field=statut}}
      </td>
      <td style="text-align: center;">{{mb_value object=$_envoi_cdm field=result}}</td>
      <td class="text">
        {{if $_envoi_cdm->statut != "traite"}}
          <form name="change-{{$_envoi_cdm->_guid}}" method="post" action="">
            {{mb_key   object=$_envoi_cdm}}
            {{mb_class object=$_envoi_cdm}}
            <input type="hidden" name="statut" value="traite"/>
            <button class="tick" type="button" title="Noter ce retour comme traité" onclick="onSubmitFormAjax(this.form, refreshListCDM);"
                    style="float: right;">
              {{tr}}CEnvoiCDM.statut.traite{{/tr}}
            </button>
          </form>
        {{/if}}
        {{foreach from=$_envoi_cdm->_ref_messages item=_message}}
          <div class="{{if $_message->type == 'warning'}}warning{{elseif $_message->type == 'error'}}error{{else}}info{{/if}}">
            {{if $_message->code}}
              {{tr}}CEnvoiCDMMessage.code.{{$_message->code}}{{/tr}}
            {{else}}
              {{mb_value object=$_message field=type}}
            {{/if}}:
          </div>
          {{mb_value object=$_message field=message}}
        {{/foreach}}
      </td>
    </tr>
  {{foreachelse}}
    <tr>
      <td colspan="6" class="empty">{{tr}}CEnvoiCDM.none{{/tr}}</td>
    </tr>
  {{/foreach}}
</table>