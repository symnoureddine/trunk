{{*
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () { 
    {{if $isImedsInstalled}}
      ImedsResultsWatcher.loadResults();
    {{/if}}
  });

  orderTabencours = function(col, way) {
    orderTabReveil(col, way, 'encours');
  };
</script>

<table class="tbl me-no-align">
  <tr>
    <th>{{mb_colonne class="COperation" field="salle_id" order_col=$order_col order_way=$order_way function=orderTabencours}}</th>
    <th>{{mb_colonne class="COperation" field="chir_id" order_col=$order_col order_way=$order_way function=orderTabencours}}</th>
    <th class="me-small-fields">{{mb_colonne class="COperation" field="_patient" order_col=$order_col order_way=$order_way function=orderTabencours}} <input type="text" name="_seek_patient_preop" value="" class="seek_patient" onkeyup="seekPatient(this);" onchange="seekPatient(this);" /></th>
    <th class="narrow">Dossier</th>
    {{if "dPsalleOp SSPI_cell see_type_anesth"|gconf}}
      <th>{{mb_colonne class="COperation" field="type_anesth" order_col=$order_col order_way=$order_way function=orderTabencours}}</th>
    {{/if}}
    {{if "dPsalleOp SSPI_cell see_ctes"|gconf}}
      <th>{{tr}}SSPI_cell.see_ctes{{/tr}}</th>
    {{/if}}
    {{if "dPsalleOp SSPI_cell see_localisation"|gconf}}
      <th>{{tr}}SSPI.Chambre{{/tr}}</th>
    {{/if}}
    <th>{{mb_colonne class="COperation" field="entree_salle" order_col=$order_col order_way=$order_way function=orderTabencours}}</th>
    <th>{{mb_colonne class="COperation" field="debut_op" order_col=$order_col order_way=$order_way function=orderTabencours}}</th>
    <th class="narrow"></th>
  </tr>
  {{foreach from=$listOperations item=_operation}}
    {{assign var=patient value=$_operation->_ref_patient}}
    {{assign var=sejour_id value=$_operation->sejour_id}}
    <tr>
      <td>{{$_operation->_ref_salle->_shortview}}</td>

      <td class="text">
        {{mb_include module=mediusers template=inc_vw_mediuser mediuser=$_operation->_ref_chir classe="me-wrapped"}}
      </td>

      <td class="text">
        {{mb_include module=system template=inc_object_notes object=$_operation float="right"}}

        <div style="float: right;">
          {{if $isImedsInstalled}}
            {{mb_include module=Imeds template=inc_sejour_labo link="#1" sejour=$_operation->_ref_sejour float="none"}}
          {{/if}}
        </div>
        <span class="CPatient-view {{if !$_operation->_ref_sejour->entree_reelle}}patient-not-arrived{{/if}} {{if $_operation->_ref_sejour->septique}}septique{{/if}}"
              onmouseover="ObjectTooltip.createEx(this, '{{$patient->_guid}}')">
          {{$patient}}
        </span>

        {{mb_include module=patients template=inc_icon_bmr_bhre}}
      </td>
      <td>
        <button class="button soins notext me-tertiary me-small" onclick="showDossierSoins('{{$_operation->sejour_id}}','{{$_operation->_id}}');">Dossier de soin</button>
        {{if $isImedsInstalled}}
          <button class="labo button notext me-tertiary me-small" onclick="showDossierSoins('{{$_operation->sejour_id}}','{{$_operation->_id}}','Imeds');">Labo</button>
        {{/if}}
        <button type="button" class="injection notext me-tertiary me-small" onclick="Operation.dossierBloc('{{$_operation->_id}}', true, 'surveillance_perop')">Dossier de bloc</button>
        {{mb_include module=patients template=inc_antecedents_allergies show_atcd=0 dossier_medical=$patient->_ref_dossier_medical patient_guid=$patient->_guid}}
      </td>

      {{if "dPsalleOp SSPI_cell see_type_anesth"|gconf}}
        <td class="text">{{mb_value object=$_operation field="type_anesth"}}</td>
      {{/if}}
      {{if "dPsalleOp SSPI_cell see_ctes"|gconf}}
        <td class="text" style="text-align: center;">
          {{if $patient->_ref_constantes_medicales->poids}}
            {{mb_value object=$patient->_ref_constantes_medicales field=poids}} kg
          {{else}}
            &mdash;
          {{/if}}
          /
          {{if $patient->_ref_constantes_medicales->taille}}
            {{mb_value object=$patient->_ref_constantes_medicales field=taille}} cm
          {{else}}
            &mdash;
          {{/if}}
        </td>
      {{/if}}
      {{if "dPsalleOp SSPI_cell see_localisation"|gconf}}
        <td class="text">
          {{mb_include module=hospi template=inc_placement_sejour sejour=$_operation->_ref_sejour which="curr"}}
        </td>
      {{/if}}
      <td>
        {{mb_value object=$_operation field=entree_salle}}
        {{mb_include module=forms template=inc_widget_ex_class_register object=$_operation event_name=entree_salle cssStyle="display: inline-block;"}}
      </td>

      <td>
        {{if $_operation->debut_op}}
          {{mb_value object=$_operation field=debut_op}}
          {{mb_include module=forms template=inc_widget_ex_class_register object=$_operation event_name=debut_intervention cssStyle="display: inline-block;"}}
        {{else}}
          -
        {{/if}}
      </td>
      <td>
        <button type="button" class="print notext"
                onclick="printDossier('{{$_operation->sejour_id}}', '{{$_operation->_id}}')"></button>
      </td>
    </tr>
  {{foreachelse}}
    <tr><td colspan="10" class="empty">{{tr}}COperation.none{{/tr}}</td></tr>
  {{/foreach}}
</table>
