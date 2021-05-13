{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=page value=0}}
<table class="tbl">
  <thead>
    <tr>
      <th class="title" colspan="10">
        {{mb_include module=facturation template=print_autre_head
                     reload_callback="Facture.printFacturesTarmedCotation();"
                     csv_callback="Facture.printFacturesTarmedCotation(null, 1);"}}
      </th>
    </tr>
    {{foreach from=$factures item=_facture name=export_factures}}
      {{if $smarty.foreach.export_factures.index === "0"}}
        <tr>
          <td class="category" colspan="10">
            {{mb_include module=system template=inc_pagination current=$page total=$nb_factures step=20
              change_page="Facture.printFacturesTarmedCotation" }}
          </td>
        </tr>
        <tr>
          <th class="narrow"></th>
          <th class="narrow">{{tr}}CFacture-date{{/tr}}</th>
          <th class="narrow">{{mb_title object=$_facture field=numero}}</th>
          <th>{{mb_title class=CPatient field=nom}}</th>
          <th>{{mb_title class=CPatient field=prenom}}</th>
          <th>{{tr}}CConsultation-derniere{{/tr}}</th>
          <th>{{tr}}CFactureCabinet-amount-invoice{{/tr}}</th>
          <th>{{tr}}CActeTarmed{{/tr}}</th>
          <th>{{tr}}CActeCaisse{{/tr}}</th>
        </tr>
      {{/if}}
      <tr>
        <td class="narrow {{$_facture->_main_statut}}">
          <button type="button" class="edit notext" onclick="Facture.edit('{{$_facture->facture_id}}', '{{$_facture->_class}}');">
            {{tr}}CFacture.see{{/tr}}
          </button>
        </td>
        <td class="narrow {{$_facture->_main_statut}}">
          {{if $_facture->cloture}}
            {{mb_value object=$_facture field=cloture}}
          {{else}}
            {{mb_value object=$_facture field=ouverture}}
          {{/if}}
        </td>
        <td class="narrow {{$_facture->_main_statut}}">
          <span onmouseover="ObjectTooltip.createEx(this, '{{$_facture->_guid}}')">
            {{$_facture->_view}}
            {{if $_facture->_current_fse}}({{tr}}CConsultation-back-fses{{/tr}}: {{if $_facture->_current_fse->_class == 'CPyxvitalFSE'}}{{$_facture->_current_fse->facture_numero}}{{else}}{{$_facture->_current_fse->numero}}{{/if}}){{/if}}
          </span>
        </td>
        <td class="text {{$_facture->_main_statut}}">
          <span onmouseover="ObjectTooltip.createEx(this, '{{$_facture->_ref_patient->_guid}}')">
            {{$_facture->_ref_patient->nom}}
          </span>
        </td>
        <td class="text {{$_facture->_main_statut}}">
          <span onmouseover="ObjectTooltip.createEx(this, '{{$_facture->_ref_patient->_guid}}')">
            {{$_facture->_ref_patient->prenom}}
          </span>
        </td>
        <td class="{{$_facture->_main_statut}}">
          {{if $_facture->_class == "CFactureEtablissement"}}
            <span onmouseover="ObjectTooltip.createEx(this, '{{$_facture->_ref_last_sejour->_guid}}')">
              {{$_facture->_ref_last_sejour->entree_prevue|date_format:$conf.date}}
            </span>
          {{elseif $_facture->_ref_last_consult->_id}}
            <span onmouseover="ObjectTooltip.createEx(this, '{{$_facture->_ref_last_consult->_guid}}')">
              {{$_facture->_ref_last_consult->_date|date_format:$conf.date}}
            </span>
          {{elseif $_facture->_ref_last_evt->_id}}
            {{$_facture->_ref_last_evt->_date|date_format:$conf.date}}
          {{/if}}
        </td>
        <td class="{{$_facture->_main_statut}}">{{mb_value object=$_facture field=_montant_avec_remise}}</td>
        {{assign var=facture_id value=$_facture->_id}}
        <td class="{{$_facture->_main_statut}}">{{$montants.$facture_id.tarmed}} {{$conf.currency_symbol|html_entity_decode}}</td>
        <td class="{{$_facture->_main_statut}}">{{$montants.$facture_id.caisse}} {{$conf.currency_symbol|html_entity_decode}}</td>
      </tr>
    {{foreachelse}}
      <tr>
        <td class="empty">
          {{tr}}No result{{/tr}}
        </td>
      </tr>
    {{/foreach}}
  </thead>
</table>
