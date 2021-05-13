{{*
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=use_concentrator value=false}}
{{if "patientMonitoring"|module_active && "patientMonitoring CMonitoringConcentrator active"|gconf}}
  {{assign var=use_concentrator value=true}}
{{/if}}

{{assign var=use_poste value=$conf.dPplanningOp.COperation.use_poste}}

<script>
  Main.add(function() {
    $('heure').innerHTML = "{{$tnow|date_format:$conf.time}}";

    {{if $isImedsInstalled}}
      ImedsResultsWatcher.loadResults();
    {{/if}}
  });

  orderTabops = function(col, way) {
    orderTabReveil(col, way, 'ops');
  };

  // faire le submit de formOperation dans le onComplete de l'ajax
  checkPersonnel = function(oFormAffectation, oFormOperation) {
    window.submitFormTiming = function(sspi_id) {
      var callback = function (sspi_id) {
        $V(oFormOperation.sspi_id, sspi_id);
        oFormOperation.entree_reveil.value = 'current';
        // si affectation renseignée, on submit les deux formulaires
        if (oFormAffectation && $V(oFormAffectation.personnel_id) != "") {
          onSubmitFormAjax(oFormAffectation, function () {
            onSubmitFormAjax(oFormOperation, refreshTabReveil.curry('ops'));
          });
        } else {
          // sinon, on ne submit que l'operation
          onSubmitFormAjax(oFormOperation, refreshTabReveil.curry('ops'));
        }
      };

      callback(sspi_id);

      {{if "patientMonitoring"|module_active && $use_concentrator}}
          App.loadJS({module: "patientMonitoring", script: "concentrator"}, function(){
            Concentrator.askPosteConcentrator(
              $V(oFormOperation.operation_id),
              "{{$bloc_id}}",
              "sspi",
              oFormOperation,
              function () {},
              1
            );
            if (oFormOperation.elements['sortie_reveil_reel'] && $V(oFormOperation.elements['sortie_reveil_reel']) != '') {
              Concentrator.importDataToConstants($V(oFormOperation.operation_id), 'sspi');
            }
          });
      {{/if}}

      window.submitFormTiming = null;
    };

    {{if $use_poste}}
      new Url('salleOp', 'ajax_count_sspis')
        .addParam('bloc_id', '{{$bloc_id}}')
        .requestJSON(
          (function(result) {
            if (result.sspi_id || !result.count) {
              return window.submitFormTiming(result.sspi_id);
            }

            new Url('salleOp', 'ajax_select_sspi')
              .addParam('bloc_id', '{{$bloc_id}}')
              .requestModal();
          }).bind(this)
        );
    {{else}}
      window.submitFormTiming();
    {{/if}}
  };
</script>

<table class="tbl me-no-align me-small">
  <tr>
    <th>{{mb_colonne class="COperation" field="salle_id" order_col=$order_col order_way=$order_way function=orderTabops}}</th>
    <th>{{mb_colonne class="COperation" field="chir_id" order_col=$order_col order_way=$order_way function=orderTabops}}</th>
    <th class="me-small-fields">{{mb_colonne class="COperation" field="_patient" order_col=$order_col order_way=$order_way function=orderTabops}} <input type="text" name="_seek_patient_preop" value="" class="seek_patient" onkeyup="seekPatient(this);" onchange="seekPatient(this);" /></th>
    <th class="narrow">Dossier</th>
    {{if "dPsalleOp SSPI_cell see_type_anesth"|gconf}}
      <th>{{mb_colonne class="COperation" field="type_anesth" order_col=$order_col order_way=$order_way function=orderTabops}}</th>
    {{/if}}
    {{if "dPsalleOp SSPI_cell see_ctes"|gconf}}
      <th>{{tr}}SSPI_cell.see_ctes{{/tr}}</th>
    {{/if}}
    {{if $use_poste}}
      <th>{{tr}}SSPI.Poste{{/tr}}</th>
    {{/if}}
    {{if "dPsalleOp SSPI_cell see_localisation"|gconf}}
      <th>{{tr}}SSPI.Chambre{{/tr}}</th>
    {{/if}}
    {{if $isbloodSalvageInstalled}}
      <th>{{tr}}SSPI.RSPO{{/tr}}</th>
    {{/if}}
    <th>{{mb_colonne class="COperation" field="sortie_salle" order_col=$order_col order_way=$order_way function=orderTabops}}</th>
    <th>{{mb_colonne class="COperation" field="entree_reveil" order_col=$order_col order_way=$order_way function=orderTabops}}</th>
    <th>Sortie sans SSPI</th>
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
              onmouseover="ObjectTooltip.createEx(this, '{{$_operation->_ref_sejour->_ref_patient->_guid}}')">
          {{$_operation->_ref_patient}}
        </span>

        {{mb_include module=patients template=inc_icon_bmr_bhre patient=$_operation->_ref_patient}}
      </td>
      <td>
        <button class="button soins notext me-tertiary" onclick="showDossierSoins('{{$_operation->sejour_id}}','{{$_operation->_id}}');">Dossier de soin</button>
        {{if $isImedsInstalled}}
          <button class="labo button notext me-tertiary" onclick="showDossierSoins('{{$_operation->sejour_id}}','{{$_operation->_id}}','Imeds');">Labo</button>
        {{/if}}
        <button type="button" class="injection notext me-tertiary" onclick="Operation.dossierBloc('{{$_operation->_id}}', true, 'surveillance_perop')">Dossier de bloc</button>
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
      {{if $use_poste}}
        <td>
          {{mb_include module=dPsalleOp template=inc_form_toggle_poste_sspi type="ops" sspi_id=$sspi_id}}
        </td>
      {{/if}}
      {{if "dPsalleOp SSPI_cell see_localisation"|gconf}}
        <td class="text">
          {{mb_include module=hospi template=inc_placement_sejour sejour=$_operation->_ref_sejour which="curr"}}
        </td>
      {{/if}}
      {{if $isbloodSalvageInstalled}}
        {{assign var=salvage value=$_operation->_ref_blood_salvage}}
        <td>
          {{if $salvage->_id}}
          <div style="float:left ; display:inline">
            <a href="#" title="Voir la procédure RSPO" onclick="viewRSPO({{$_operation->_id}});">
            <img src="images/icons/search.png" title="Voir la procédure RSPO" alt="vw_rspo">
            {{if $salvage->_totaltime > "00:00:00"}}
              Débuté à {{$salvage->_recuperation_start|date_format:$conf.time}}
            {{else}}
              Non débuté
            {{/if}}
          </a>
          </div>
          {{if $salvage->_totaltime|date_format:$conf.time > "05:00"}}
          <div style="float:right; display:inline">

          {{me_img src="warning.png" icon="warning" class="me-warning" title="Durée légale bientôt atteinte" alt="alerte-durée-RSPO"}}
          {{/if}}
          </div>
          {{else}}
            Non inscrit
          {{/if}}
        </td>
      {{/if}}
      <td>
        {{mb_value object=$_operation field="sortie_salle"}}
      </td>
      <td>
        {{if $modif_operation}}

        {{if $personnels !== null && $personnels|@count}}
        <form name="selPersonnel{{$_operation->_id}}" method="post" class="prepared">
          <input type="hidden" name="m" value="personnel" />
          <input type="hidden" name="dosql" value="do_affectation_aed" />
          <input type="hidden" name="del" value="0" />
          <input type="hidden" name="object_id" value="{{$_operation->_id}}" />
          <input type="hidden" name="object_class" value="{{$_operation->_class}}" />
          <input type="hidden" name="tag" value="reveil" />
          <input type="hidden" name="realise" value="0" />
          <select name="personnel_id" style="max-width: 120px;">
            <option value="">&mdash; Personnel</option>
            {{foreach from=$personnels item="personnel"}}
            <option value="{{$personnel->_id}}">{{$personnel->_ref_user}}</option>
            {{/foreach}}
          </select>
        </form>
        {{/if}}

        <form name="editEntreeReveilOpsFrm{{$_operation->_id}}" method="post" class="prepared">
          <input type="hidden" name="m" value="planningOp" />
          <input type="hidden" name="dosql" value="do_planning_aed" />
          {{mb_key object=$_operation}}
          <input type="hidden" name="del" value="0" />
          <input type="hidden" name="entree_reveil" />
          <input type="hidden" name="sspi_id" />
          <button class="tick notext me-tertiary me-small" type="button" onclick="checkPersonnel(getForm('selPersonnel{{$_operation->_id}}'), this.form);">{{tr}}Modify{{/tr}}</button>
        </form>

        {{foreach from=$_operation->_ref_affectations_personnel.reveil item=curr_affectation}}
          <br />
          <form name="delPersonnel{{$curr_affectation->_id}}" method="post" class="prepared">
            <input type="hidden" name="m" value="personnel" />
            <input type="hidden" name="dosql" value="do_affectation_aed" />
            <input type="hidden" name="del" value="1" />
            {{mb_key object=$curr_affectation}}
            <button type="button" class="trash notext me-tertiary me-small" onclick="onSubmitFormAjax(this.form, refreshTabReveil.curry('ops'))">
              {{tr}}Delete{{/tr}}
            </button>
          </form>
          {{$curr_affectation->_ref_personnel->_ref_user}}
        {{/foreach}}
        {{else}}
          -
        {{/if}}

        {{mb_include module=forms template=inc_widget_ex_class_register_multiple object=$_operation cssStyle="display: inline-block;"}}
      </td>
      <td class="button">
        {{if $modif_operation}}
          <form name="editSortieReveilOpsFrm{{$_operation->_id}}" method="post" class="prepared">
            {{mb_key    object=$_operation}}
            <input type="hidden" name="m" value="planningOp"/>
            <input type="hidden" name="sortie_sans_sspi" value="current" />
            <input type="hidden" name="dosql" value="do_planning_aed"/>
            <button class="tick notext me-tertiary me-small" type="button" onclick="onSubmitFormAjax(this.form, refreshTabReveil.curry('ops'))">
              {{tr}}Modify{{/tr}}
            </button>
          </form>
        {{else}}-{{/if}}
      </td>
      <td>
        <button type="button" class="print notext me-tertiary me-small"
          onclick="printDossier('{{$_operation->sejour_id}}', '{{$_operation->_id}}')"></button>
      </td>
    </tr>
  {{foreachelse}}
    <tr><td colspan="23" class="empty">{{tr}}COperation.none{{/tr}}</td></tr>
  {{/foreach}}
</table>

{{mb_include module=forms template=inc_widget_ex_class_register_multiple_end event_name=entree_reveil object_class="COperation"}}
