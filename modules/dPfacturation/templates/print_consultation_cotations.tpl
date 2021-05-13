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
    <th class="title" colspan="13">
      {{mb_include module=facturation template=print_autre_head reload_callback="Facture.printCotations();"
        csv_callback="Facture.printCotations(null, 1);" title="CFacture.cotations_consultation"
        title_2="CFacture.cotations_consultation-desc"}}
      <span style="float: left;">
        <select name="categorie_id" onchange="Facture.printCotations(null, null, $V(this));">
          <option value="0">&mdash; {{tr}}CConsultation-categorie_id-choose{{/tr}}</option>
          {{foreach from=$categories item=_categorie}}
            <option value="{{$_categorie->_id}}" {{if $_categorie->_id == $categorie_id}}selected{{/if}}>
              {{$_categorie->_view}}
            </option>
          {{/foreach}}
        </select>
      </span>
    </th>
  </tr>
  {{foreach from=$actes item=_acte name=export_actes}}
    {{assign var=_consultation  value=$_acte->_ref_object}}
    {{if $smarty.foreach.export_actes.index === "0"}}
      <tr>
        <td class="category" colspan="13">
          {{mb_include module=system template=inc_pagination current=$page total=$nb_actes step=20
          change_page="Facture.printCotations" }}
        </td>
      </tr>
      <tr>
        <th class="narrow">{{tr}}CPatient-nom{{/tr}}</th>
        <th class="narrow">{{tr}}CPatient-prenom{{/tr}}</th>
        <th class="narrow">{{tr}}CPatient-naissance{{/tr}}</th>
        <th class="narrow">{{tr}}Export-Date_intervention{{/tr}}</th>
        <th class="narrow">{{tr}}Export-operateur1{{/tr}}</th>
        <th class="narrow">{{tr}}Export-tarmed{{/tr}}</th>
        <th class="narrow">{{tr}}CActeTarmed-cote{{/tr}}</th>
        <th class="narrow">{{tr}}Export-hors_tarmed{{/tr}}</th>
        <th class="narrow">{{tr}}CEditPdf.diagnostic{{/tr}}</th>
        <th class="narrow">{{tr}}Export-temps_operatoire{{/tr}}</th>
        <th class="narrow">{{tr}}Export-operateur2{{/tr}}</th>
        <th class="narrow">{{tr}}Export-complications{{/tr}}</th>
        <th class="narrow">{{tr}}Export-duree_hop{{/tr}}</th>
      </tr>
    {{/if}}
    <tr>
      <td>{{mb_value object=$_consultation->_ref_patient field=nom}}</td>
      <td>{{mb_value object=$_consultation->_ref_patient field=prenom}}</td>
      <td>{{mb_value object=$_consultation->_ref_patient field=naissance}}</td>
      <td>{{mb_value object=$_consultation field=_date}}</td>
      <td>{{mb_value object=$_consultation field=_praticien_id}}</td>
      <td>{{if $_acte->_class == "CActeTarmed"}}{{mb_value object=$_acte field=code}}{{/if}}</td>
      <td>{{if $_acte->_class == "CActeTarmed" && $_acte->cote}}{{mb_value object=$_acte field=cote}}{{/if}}</td>
      <td>
        {{if $_acte->_class == "CActeCaisse"}}
          {{mb_value object=$_acte field=code}}: {{mb_value object=$_acte->_ref_prestation_caisse field=libelle}}
        {{/if}}
      </td>
      <td>{{mb_value object=$_consultation field=motif}}</td>
      <td>{{mb_value object=$_consultation field=rques}}</td>
      <td>{{mb_value object=$_consultation field=examen}}</td>
      <td>{{mb_value object=$_consultation field=histoire_maladie}}</td>
      <td>{{mb_value object=$_consultation field=conclusion}}</td>
    {{foreachelse}}
    <tr>
      <td {{if $categorie_id}}class="empty"{{/if}}>
        {{if !$categorie_id}}
          <div class="small-info"> {{tr}}CConsultation-categorie_id-select{{/tr}}</div>
        {{else}}
          {{tr}}No result{{/tr}}
        {{/if}}
      </td>
    </tr>
  {{/foreach}}
  </thead>
</table>
