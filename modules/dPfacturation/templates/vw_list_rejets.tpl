{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="tbl">
  <tr>
    <th colspan="8" class="title">{{tr}}CFactureRejet{{/tr}} ({{$rejets|@count}})</th>
  </tr>
  <tr>
    <th class="narrow">{{mb_label class=CFactureRejet field=date}}</th>
    <th>{{mb_label class=CFactureRejet field=name_assurance}}</th>
    <th class="narrow">{{mb_label class=CFactureRejet field=_date_facture}} / {{mb_label class=CFactureRejet field=num_facture}}</th>
    <th class="narrow">{{mb_label class=CFactureRejet field=file_name}}</th>
    <th class="narrow">{{mb_label class=CFactureRejet field=statut}}</th>
    <th>{{mb_label class=CFactureRejet field=_patient}}</th>
    <th>{{mb_label class=CFactureRejet field=motif_rejet}}</th>
    <th>{{mb_label class=CFactureRejet field=_contact}}</th>
  </tr>
  {{foreach from=$rejets item=_rejet}}
    <tr>
      <td>{{mb_value object=$_rejet field=date}}</td>
      <td>{{mb_value object=$_rejet field=name_assurance}}</td>
      <td>{{mb_value object=$_rejet field=_date_facture}} / {{mb_value object=$_rejet field=num_facture}}</td>
      <td>{{$_rejet->file_name|spancate:20:"...":false|nl2br}}</td>
      <td class="button">
        {{mb_value object=$_rejet field=statut}}
        {{if $_rejet->statut != "traite"}}
          <form name="change-{{$_rejet->_guid}}" method="post" action="">
            {{mb_key   object=$_rejet}}
            {{mb_class object=$_rejet}}
            <input type="hidden" name="statut" value="traite"/>
            <button class="tick" type="button" title="Noter ce rejet comme traité" onclick="Rejet.traiterRejet(this.form);">{{tr}}CFactureRejet.statut.traite{{/tr}}</button>
          </form>
        {{/if}}
      </td>
      <td>
        {{if $_rejet->_patient}}
          <a href="#" onmouseover="ObjectTooltip.createEx(this, '{{$_rejet->_patient->_guid}}')">{{$_rejet->_patient->_view}}</a>
        {{/if}}
      </td>
      <td style="padding:0px;">
        <table class="main form">
          <tr>
            <th class="section" colspan="2">
              {{tr}}CFactureRejet._status.{{$_rejet->_status_in}}{{/tr}} => {{tr}}CFactureRejet._status.{{$_rejet->_status_out}}{{/tr}}
            </th>
          </tr>
          <tr>
            <td colspan="2">{{$_rejet->_commentaire}}</td>
          </tr>

          {{foreach from=$_rejet->_erreurs item=_erreur}}
            <tr>
              <td colspan="2">
                <strong>{{tr}}CFactureRejet.type_error.{{$_rejet->_pending}}{{/tr}}</strong>
              </td>
            </tr>
            <tr>
              <td style="text-align: right;">{{tr}}CFactureRejet.code{{/tr}}</td>
              <td>{{$_erreur.code}}</td>
            </tr>
            {{if isset($_erreur.error_value|smarty:nodefaults)}}
              <tr>
                <td style="text-align: right;">{{tr}}CFactureRejet.record_id{{/tr}}</td>
                <td>{{$_erreur.record_id}}</td>
              </tr>
              <tr>
                <td style="text-align: right;">{{tr}}CFactureRejet.error_value{{/tr}}</td>
                <td><strong>{{$_erreur.error_value}}</strong></td>
              </tr>
              <tr>
                <td style="text-align: right;">{{tr}}CFactureRejet.valid_value{{/tr}}</td>
                <td><strong>{{$_erreur.valid_value}}</strong></td>
              </tr>
            {{/if}}
            <tr>
              <td style="text-align: right;">{{tr}}CFactureRejet.justification{{/tr}}</td>
              <td><strong>{{$_erreur.text}}</strong></td>
            </tr>
          {{/foreach}}
        </table>
      </td>
      <td>
        {{foreach from=$_rejet->_contact item=_contact}}
          {{$_contact}}<br/>
        {{/foreach}}
      </td>
    </tr>
    {{foreachelse}}
    <tr>
      <td class="empty" colspan="8">{{tr}}CFactureRejet.none{{/tr}}</td>
    </tr>
  {{/foreach}}
</table>
