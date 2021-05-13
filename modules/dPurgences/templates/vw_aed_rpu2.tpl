{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=urgences script=echelle_tri ajax=true}}
{{mb_script module=urgences script=motif ajax=true}}
{{mb_script module=cabinet  script=dossier_medical ajax=true}}
{{mb_script module=patients script=pat_selector ajax=1}}

{{assign var=unique_id value="-"|uniqid}}
<script>
  Main.add(function() {
    EchelleTri.unique_id = '{{$unique_id}}';
    EchelleTri.chir_id = '{{$app->user_id}}';
    var sejour_id = '{{$rpu->sejour_id}}';
    if (sejour_id) {
      EchelleTri.refreshConstantesMedicalesTri('CSejour-'+sejour_id);
      EchelleTri.refreshAntecedentsPatient();
    }
    EchelleTri.requestInfoPatTri();
    {{if $rpu->_ref_motif->_id}}
      Motif.edit('{{$rpu->_ref_motif->_id}}', 1, 1);
    {{/if}}
  });
</script>

<table class="form me-no-box-shadow me-no-align">
  <tr>
    <td style="width:60%;">
      <form name="editRPUtri" action="?" method="post" onsubmit="return checkForm(this);">
        {{mb_class object=$rpu}}
        {{mb_key   object=$rpu}}
        <input type="hidden" name="m" value="urgences" />
        <input type="hidden" name="postRedirect" value="m=urgences&dialog=vw_aed_rpu" />
        <input type="hidden" name="del" value="0" />
        <input type="hidden" name="sejour_id" value="{{$sejour->_id}}" />
        <input type="hidden" name="_annule" value="{{$rpu->_annule|default:"0"}}" />
        <input type="hidden" name="_bind_sejour" value="1" />
        {{mb_field object=$rpu field=echelle_tri_valide hidden=true}}

        <table class="form">
          <tr>
            {{if $rpu->_id}}
            <th class="title modify" colspan="4">
              {{mb_include module=system template=inc_object_notes      object=$sejour}}
              {{mb_include module=system template=inc_object_idsante400 object=$rpu}}
              {{mb_include module=system template=inc_object_history    object=$rpu}}
              <a class="action" style="float: right;" title="Modifier uniquement le sejour" href="?m=planningOp&tab=vw_edit_sejour&sejour_id={{$sejour->_id}}">
                {{me_img src="edit.png" icon="edit" class="me-primary" alt="modifier"}}
              </a>

              {{tr}}CRPU-title-modify{{/tr}}
              '{{$rpu}}'
              {{mb_include module=planningOp template=inc_vw_numdos nda_obj=$sejour}}
            </th>
            {{else}}
            <th class="title me-th-new" colspan="4">
              {{tr}}CRPU-title-create{{/tr}}
              {{if $sejour->_NDA}}
                pour le dossier
                {{mb_include module=planningOp template=inc_vw_numdos nda_obj=$sejour}}
              {{/if}}
            </th>
            {{/if}}
          </tr>
        
          {{if $rpu->_annule}}
          <tr>
            <th class="category cancelled" colspan="4">
            {{tr}}CRPU-_annule{{/tr}}
            </th>
          </tr>
          {{/if}}
          
          <tr>
            <th>{{mb_label object=$rpu field="_responsable_id"}}</th>
            <td>
              <select name="_responsable_id" style="width: 15em;" class="{{$rpu->_props._responsable_id}}">
                <option value="">&mdash; {{tr}}Choose{{/tr}}</option>
                {{mb_include module=mediusers template=inc_options_mediuser selected=$rpu->_responsable_id list=$listResponsables}}
              </select>
            </td>
          </tr>
          <tr>
            <th>{{mb_label object=$rpu field=_entree}}</th>
            <td>{{mb_field object=$rpu field=_entree form="editRPUtri" register=true}}</td>
          </tr>
          {{if "dPurgences Display see_ide_ref"|gconf || "dPurgences CRPU impose_ide_referent"|gconf}}
            <tr>
              <th>{{mb_label object=$rpu field=ide_responsable_id}}</th>
              <td colspan="3">
                {{mb_field object=$rpu field=ide_responsable_id hidden=true}}
                <input type="text" name="ide_responsable_id_view" class="autocomplete" value="{{$rpu->_ref_ide_responsable->_view}}"
                       placeholder="&mdash; {{tr}}Choose{{/tr}}"/>
                <script>
                  Main.add(function () {
                    var form = getForm("editRPUtri");
                    new Url("dPurgences", "ajax_ide_responsable_autocomplete")
                      .autoComplete(form.ide_responsable_id_view, null, {
                        minChars: 2,
                        method: "get",
                        select: "view",
                        dropdown: true,
                        updateElement: function(selected) {
                          var id = selected.get("id");
                          $V(form.ide_responsable_id, id);
                          $V(form.ide_responsable_id_view, selected.get("name"));
                        }.bind(form)
                      });
                  });
                </script>
              </td>
            </tr>
          {{/if}}
          <tr>
            <th>{{mb_label object=$rpu field=ioa_id}}</th>
            <td colspan="3">
              {{mb_field object=$rpu field=ioa_id hidden=true}}
              <input type="text" name="ioa_id_view" class="autocomplete" value="{{$rpu->_ref_ioa}}"
                     placeholder="&mdash; {{tr}}Choose{{/tr}}"/>
              <script>
                Main.add(function() {
                  var form = getForm("editRPUtri");
                  new Url("urgences", "ajax_ide_responsable_autocomplete")
                    .autoComplete(form.ioa_id_view, null, {
                      minChars: 2,
                      method: "get",
                      select: "view",
                      dropdown: true,
                      updateElement: function(selected) {
                        var id = selected.get("id");
                        $V(form.ioa_id, id);
                        $V(form.ioa_id_view, selected.get("name"));
                      }.bind(form)
                    });
                });
              </script>
            </td>
          </tr>
          <tr>
            <th>{{mb_label object=$rpu field=pec_ioa}}</th>
            <td colspan="3">
              {{mb_field object=$rpu field=pec_ioa form="editRPUtri" register=true}}
            </td>
          </tr>
          <tr>
            <th>
              {{mb_include module=patients template=inc_button_pat_anonyme form=editRPUtri patient_id=$rpu->_patient_id
                          input_name="_patient_id"}}
              <input type="hidden" name="_patient_id" class="{{$sejour->_props.patient_id}}" ondblclick="PatSelector.init()"
                     value="{{$rpu->_patient_id}}" onchange="EchelleTri.onChangePatient();" />
              {{mb_label object=$rpu field="_patient_id"}}
            </th>
            <td>
              {{assign var=can_edit_pat value=false}}
              {{if $conf.dPurgences.allow_change_patient || !$sejour->_id || $app->user_type == 1}}
                {{assign var=can_edit_pat value=true}}
              {{/if}}
              <input type="text" name="_patient_view" style="width: 15em;" value="{{$patient->_view}}" 
                {{if $can_edit_pat}}
                  onfocus="PatSelector.init()" 
                {{/if}}
              readonly="readonly" />
              {{if $conf.dPurgences.allow_change_patient || !$sejour->_id || $app->user_type == 1}} 
                <button type="button" class="search notext" onclick="PatSelector.init()">{{tr}}Search{{/tr}}</button>
              {{/if}}
              <script>
                PatSelector.init = function(){
                  this.sForm = "editRPUtri";
                  this.sId   = "_patient_id";
                  this.sView = "_patient_view";
                  this.pop();
                }
              </script>
              {{if $patient->_id}}
              <button id="button-edit-patient" type="button" class="edit notext"
                onclick="location.href='?m=patients&tab=vw_edit_patients&patient_id='+this.form._patient_id.value">
                {{tr}}Edit{{/tr}}
              </button>
              {{/if}}
              <br/>
              <input type="text" name="_seek_patient" style="width: 13em; {{if !$can_edit_pat}}display:none;{{/if}}"
                     placeholder="{{tr}}fast-search{{/tr}}" autocomplete onblur="$V(this, '')"  />

              <script>
                Main.add(function(){
                  {{if $can_edit_pat}}
                    var form = getForm("editRPUtri");
                    var url = new Url("system", "ajax_seek_autocomplete");
                    url.addParam("object_class", "CPatient");
                    url.addParam("field", "patient_id");
                    url.addParam("view_field", "_patient_view");
                    url.addParam("input_field", "_seek_patient");
                    url.autoComplete(form.elements._seek_patient, null, {
                      minChars: 3,
                      method: "get",
                      select: "view",
                      dropdown: false,
                      width: "300px",
                      afterUpdateElement: function(field,selected){
                        $V(field.form._patient_id, selected.getAttribute("id").split("-")[2]);
                        $V(field.form.elements._patient_view, selected.down('.view').innerHTML);
                        $V(field.form.elements._seek_patient, "");
                      }
                    });
                    Event.observe(form.elements._seek_patient, 'keydown', PatSelector.cancelFastSearch);
                  {{/if}}
                });
              </script>

            </td>
          </tr>

          {{if "maternite"|module_active && @$modules.maternite->_can->read && (!$patient || $patient->sexe != "m")}}
            <tr>
              <th>{{tr}}CGrossesse{{/tr}}</th>
              <td>
                {{mb_include module=maternite template=inc_input_grossesse object=$sejour patient=$patient}}
              </td>
            </tr>
          {{/if}}

          <tr>
            <th>{{mb_label object=$rpu field=box_id}}</th>
            <td>
              {{mb_include module=hospi template=inc_select_lit field=box_id selected_id=$rpu->box_id ajaxSubmit=0 listService=$services}}
              <button type="button" class="cancel opacity-60 notext" onclick="this.form.elements['box_id'].selectedIndex = 0"></button>
              &mdash; {{tr}}CRPU-_service_id{{/tr}} :
              {{if $services|@count == 1}}
                {{assign var=first_service value=$services|@first}}
                {{$first_service->_view}}
              {{else}}
                <select name="_service_id" class="{{$sejour->_props.service_id}}">
                  <option value="">&mdash; {{tr}}Choose{{/tr}}</option>
                  {{foreach from=$services item=_service}}
                    <option value="{{$_service->_id}}" {{if "Urgences" == $_service->nom}} selected="selected" {{/if}}>
                      {{$_service->_view}}
                    </option>
                  {{/foreach}}
                </select>
              {{/if}}
              <br/>
              <script>
                Main.add(function(){
                  var form = getForm("editRPUtri");
                  if (form.elements._service_id) {
                    var box = form.elements.box_id;
                    box.observe("change", function(event){
                      var service_id = box.options[box.selectedIndex].up("optgroup").get("service_id");
                      $V(form.elements._service_id, service_id);
                    });
                  }
                });
              </script>
            </td>
          </tr>
          <tr>
            <th>{{mb_label object=$rpu field=pec_douleur}}</th>
            <td>
              {{if $rpu->echelle_tri_valide}}
                {{mb_field object=$rpu field=pec_douleur class="autocomplete" form="editRPUtri" readonly=1}}
              {{else}}
                {{mb_field object=$rpu field=pec_douleur class="autocomplete" form="editRPUtri"
                    aidesaisie="validate: function() { form.onsubmit() },validateOnBlur: 0,resetSearchField: 0,resetDependFields: 0"}}
              {{/if}}
            </td>
          </tr>
          <tr>
            <td class="button" colspan="2">
              {{if $rpu->_id}}
                {{if !$rpu->echelle_tri_valide || $app->_ref_user->isAdmin()}}
                  <button class="modify" type="submit">{{tr}}Save{{/tr}}</button>
                  {{mb_ternary var=annule_text test=$sejour->annule value="Rétablir" other="Annuler"}}
                  {{mb_ternary var=annule_class test=$sejour->annule value="change" other="cancel"}}

                  {{if $sejour->type === "urg"}}
                    <button class="{{$annule_class}}" type="button" onclick="Urgences.cancelRPU();">
                      {{$annule_text}}
                    </button>
                  {{/if}}

                  {{if $can->admin}}
                    <button class="trash" type="button" onclick="confirmDeletion(this.form,{typeName:'l\'urgence ',objName:'{{$rpu->_view|smarty:nodefaults|JSAttribute}}'})">
                      {{tr}}Delete{{/tr}}
                    </button>
                  {{/if}}
                {{/if}}

                <button type="button" class="search" onclick="Modal.open($('modale_dossier_patient'));">Dossier patient</button>
                {{if "ecap"|module_active && $current_group|idex:"ecap"|is_numeric}}
                  {{mb_include module=ecap template=inc_button_dhe_urgence sejour_id=$sejour->_id}}
                {{/if}}
                <button type="button" class="tick" onclick="$V(this.form.echelle_tri_valide, 1);this.form.submit();"
                        {{if $rpu->echelle_tri_valide}}style="display:none;"{{/if}} id="echelle_tri_valided">
                  {{tr}}CRPU-echelle_tri_valided{{/tr}}
                </button>
                <button type="button" class="cancel" onclick="$V(this.form.echelle_tri_valide, 0);this.form.submit();"
                  {{if !$rpu->echelle_tri_valide}}style="display:none;"{{/if}} id="echelle_tri_invalided">
                  {{tr}}CRPU-echelle_tri_invalided{{/tr}}
                </button>
              {{else}}
                <button class="submit" type="submit">{{tr}}Create{{/tr}}</button>
              {{/if}}
            </td>
          </tr>
        </table> 
      </form>
      <fieldset class="me-padding-bottom-16" style="width:48%;float:left;">
        {{mb_include module=urgences template=inc_tooltip_cte_ccmu}}
        <legend>{{tr}}CPatient-back-constantes{{/tr}}</legend>
        <div id="constantes-tri" style="position: relative; height: 400px;"></div>
      </fieldset>

      <div style="float:left;width:48%;" id="form-echelle_tri">
        {{mb_include module=urgences template=vw_echelle_tri}}
      </div>
    </td>
    <td class="me-valign-top" style="width:40%;">
      <div id="form-edit-complement">
        {{mb_include module=urgences template=inc_form_complement}}
      </div>
      <div id="form-question_motif" style="margin-bottom: 2px;">
        {{mb_include module=urgences template=inc_form_questions_motif}}
      </div>
      <br/>
      <div style="display: none;" id="modale_dossier_patient">
        <table class="form">
          <tr>
            <th class="category" colspan="2">
              {{tr}}CPatient-Patient folder{{/tr}}
              <button type="button" class="cancel notext" style="float: right" onclick="Control.Modal.close();">{{tr}}Close{{/tr}}</button>
            </th>
          </tr>
          <tr>
            <td colspan="2" rowspan="3">
              <div id="antecedentsanesth">
                {{if !$rpu->_id}}
                  <div class="empty">{{tr}}CPatient.none_selected{{/tr}}</div>
                {{/if}}
              </div>
            </td>
          </tr>
        </table>

        <fieldSet>
          <legend>{{tr}}CPatient.infos{{/tr}}</legend>
          <div class="text" id="infoPat">
            <div class="empty">{{tr}}CPatient.none_selected{{/tr}}</div>
          </div>
        </fieldSet>
      </div>
      <div id="view_motif_rpu"></div>
    </td>
  </tr>
</table>