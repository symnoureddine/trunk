{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=patients script=anticipated_directives ajax=$ajax}}

{{assign var=allowed_modify value=$app->user_prefs.allowed_modify_identity_status}}
{{mb_default var=use_id_interpreter value=0}}
{{mb_default var=validate_identity value=0}}

{{assign var=readonly value=0}}

{{if $patient->_id && $patient->status && !in_array($patient->status, array('VIDE', 'PROV'))}}
  {{assign var=readonly value=1}}
{{/if}}

<script>
  Main.add(function () {
    var i,
      patient_identite = $("patient_identite"),
      list = patient_identite.select(".prenoms_list input"),
      button = patient_identite.select("button.down.notext");
    for (i = 0; i < list.length; i++) {
      if ($V(list[i])) {
        Patient.togglePrenomsList(button[0]);
        break;
      }
    }
    {{if $patient->_id}}
      Patient.refreshInfoTutelle('{{$patient->tutelle}}');
    {{else}}
      Patient.checkDoublon();
    {{/if}}

    {{if !$readonly}}
    InseeFields.initCPVille("editFrm", "cp", "ville", "commune_naissance_insee", "pays");
    InseeFields.initCPVille("editFrm", "cp_naissance", "lieu_naissance", "commune_naissance_insee", "_pays_naissance_insee");
    {{/if}}
  });
</script>

<div id="alert_tutelle"></div>

{{if $validate_identity}}
  <div class="small-info">
    {{tr}}CPatient-Ways to validate identity{{/tr}}
  </div>
{{elseif in_array($patient->status, array('RECUP', 'QUAL', 'VALI'))}}
  <div class="small-warning">
    {{if in_array($patient->status, array('VALI', 'QUAL'))}}
      {{if $patient->status === 'VALI'}}
        {{if $allowed_modify}}
          {{tr}}CSourceIdentite-Ask devalidate identity{{/tr}} :
        {{else}}
          {{tr}}CSourceIdentite-Cannot devalidate identity{{/tr}}
        {{/if}}
      {{elseif $patient->status === 'QUAL'}}
        {{if $allowed_modify}}
          {{tr}}CSourceIdentite-Ask dequalify identity{{/tr}} :
        {{else}}
          {{tr}}CSourceIdentite-Cannot dequalify identity{{/tr}}
        {{/if}}
      {{/if}}
    {{else}}
      {{tr}}CSourceIdentite-Ask derecup identity{{/tr}} :
    {{/if}}
    {{if $patient->status === 'RECUP' || $allowed_modify}}
      <button type="button" class="tick" onclick="SourceIdentite.retrogateStatus();">
        {{if $patient->status === 'RECUP'}}
          {{tr}}CSourceIdentite-Derecup identity{{/tr}}
        {{elseif $patient->status === 'QUAL'}}
          {{tr}}CSourceIdentite-Dequalify identity{{/tr}}
        {{else}}
          {{tr}}CSourceIdentite-Devalidate identity{{/tr}}
        {{/if}}
      </button>
    {{/if}}
  </div>
{{/if}}

<div class="me-poc-container">
  <div class="me-list-categories">
    <div class="me-categorie-form identite {{if !$patient->_id}}identite_new{{/if}}" id="patient_identite">
      <div class="categorie-form_titre">
        Identité

        <span style="float:right">
          {{if !$patient->_ref_sources_identite|@count || $patient->status === 'PROV'}}
            {{if $app->user_prefs.LogicielLectureVitale == 'mbHost'}}
              {{assign var=autoRead value=false}}
              {{if !$patient->_id}}
                {{assign var=autoRead value=true}}
              {{/if}}

              {{mb_include module=mbHost template=inc_vitale operation='create' autoRead=$autoRead formName='editFrm'}}
            {{/if}}
          {{/if}}

          {{if $patient->_id}}
            <button type="button" onclick="SourceIdentite.openList();" class="search me-tertiary">{{tr}}CSourceIdentite|pl{{/tr}}</button>
          {{/if}}

          <button type="button" class="add me-secondary" onclick="Patient.addJustificatif('{{$patient->_id}}')" class="add">{{tr}}CPatient-Justificatif{{/tr}}</button>

          {{if "ameli"|module_active && $patient->_id}}
            {{mb_include module=ameli template=services/inc_insiicir_button}}
          {{/if}}
        </span>

      </div>
      <div class="categorie-form_photo" id="{{$patient->_guid}}-identity">
          {{if $patient->_id}}
           {{mb_include template=inc_vw_photo_identite size="60" mode="edit"}}
          {{/if}}
      </div>
      <div class="categorie-form_fields">
        <div class="categorie-form_fields-group">
            <div id="doublon-patient"></div>
            {{me_form_field mb_object=$patient mb_field="nom"}}
              {{if $readonly}}
                {{mb_field object=$patient field="nom" readonly=true}}
              {{else}}
                  {{mb_field object=$patient field="nom" onchange="Patient.checkDoublon(); Patient.copyIdentiteAssureValues(this)"}}
                  {{if !$patient->_id}}
                    <button type="button" class="me-tertiary notext anonyme" style="padding: 0;" onclick="Patient.anonymous()"
                            tabIndex="1000"></button>
                  {{/if}}
              {{/if}}
              {{if $use_id_interpreter}}
                <button type="button" class="fas fa-id-card notext me-tertiary me-dark"
                        onclick="IdInterpreter.open(this.form, '{{$patient->_guid}}')">
                    {{tr}}CIdInterpreter.fill_from_image{{/tr}}
                </button>
              {{/if}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="prenom"}}
            {{if $readonly}}
                {{mb_field object=$patient field="prenom" readonly=true}}
            {{else}}
                {{mb_field object=$patient field="prenom" onchange="Patient.checkDoublon(); Patient.copyIdentiteAssureValues(this); Patient.copyPrenom(this);"}}
            {{/if}}
              <button type="button" class="down notext me-tertiary me-dark" onclick="Patient.togglePrenomsList(this)"
                      tabIndex="1000">{{tr}}Add{{/tr}}</button>
            {{/me_form_field}}

            <div class="prenoms_list" style="display: none;">
              {{me_form_field mb_object=$patient mb_field="prenoms"}}
                {{if $readonly}}
                  {{mb_field object=$patient field=prenoms readonly=true}}
                {{else}}
                  {{mb_field object=$patient field="prenoms" onchange="Patient.checkDoublon(); Patient.copyIdentiteAssureValues(this); Patient.copyPrenom(this);"}}
                {{/if}}
              {{/me_form_field}}
            </div>

            {{me_form_field mb_object=$patient mb_field="prenom_usuel"}}
              {{if $readonly}}
                {{mb_field object=$patient field="prenom_usuel" readonly=true}}
              {{else}}
                {{mb_field object=$patient field="prenom_usuel"}}
              {{/if}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="nom_jeune_fille"}}
            {{if $readonly}}
                {{mb_field object=$patient field="nom_jeune_fille" readonly=true}}
            {{else}}
              {{mb_field object=$patient field="nom_jeune_fille" onchange="Patient.checkDoublon(); Patient.copyIdentiteAssureValues(this)"}}
              <button type="button" class="carriage_return notext me-tertiary" title="{{tr}}CPatient.name_recopy{{/tr}}"
                      onclick="$V(getForm('editFrm').nom_jeune_fille, $V(getForm('editFrm').nom));" tabIndex="1000"></button>
            {{/if}}
            {{/me_form_field}}

            {{me_form_field layout=true mb_object=$patient mb_field="sexe"}}
              {{if $readonly}}
                {{mb_field object=$patient field="sexe" readonly=true}}
              {{else}}
                {{mb_field object=$patient field="sexe" canNull=false typeEnum=radio
                onchange="Patient.copyIdentiteAssureValues(this); Patient.changeCivilite();"}}
            {{/if}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="naissance"}}
              {{if $readonly}}
                  {{mb_field object=$patient field="naissance" readonly=true}}
              {{else}}
                  {{mb_field object=$patient field="naissance"
                  onchange="Patient.checkDoublon(); Patient.copyIdentiteAssureValues(this); Patient.changeCivilite();"}}
              {{/if}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="lieu_naissance"}}
              {{mb_field object=$patient field=commune_naissance_insee hidden=true}}
              {{if $readonly}}
                {{mb_field object=$patient field=lieu_naissance hidden=true}}
                <div class="me-field-content">
                  {{mb_value object=$patient field=lieu_naissance}}
                </div>
              {{else}}
                {{mb_field object=$patient field="lieu_naissance" onchange="Patient.copyIdentiteAssureValues(this)"}}
              {{/if}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="civilite"}}
            {{assign var=civilite_locales value=$patient->_specs.civilite}}
              <select name="civilite" onchange="Patient.copyIdentiteAssureValues(this);">
                <option value="">&mdash; {{tr}}Choose{{/tr}}</option>
                  {{foreach from=$civilite_locales->_locales key=key item=_civilite}}
                    <option value="{{$key}}" {{if $key == $patient->civilite}}selected{{/if}}>
                        {{tr}}CPatient.civilite.{{$key}}-long{{/tr}} - ({{$_civilite}})
                    </option>
                  {{/foreach}}
              </select>
            {{/me_form_field}}
        </div>
        <div class="categorie-form_fields-group">
            {{me_form_field mb_object=$patient mb_field="rang_naissance"}}
            {{mb_field object=$patient field="rang_naissance" emptyLabel=Select}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="cp_naissance"}}
              {{if $readonly}}
                {{mb_field object=$patient field=cp_naissance hidden=true}}
                <div class="me-field-content">
                  {{mb_value object=$patient field=cp_naissance}}
                </div>
              {{else}}
                {{mb_field object=$patient field="cp_naissance" onchange="Patient.copyIdentiteAssureValues(this)"}}
              {{/if}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="deces"}}
              {{mb_field object=$patient field="deces" register=true form=editFrm}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="_pays_naissance_insee"}}
              {{if $readonly}}
                {{mb_field object=$patient field="_pays_naissance_insee" hidden=true}}
                <div class="me-field-content">
                  {{mb_value object=$patient field=_pays_naissance_insee}}
                </div>
              {{else}}
                {{mb_field object=$patient field="_pays_naissance_insee" onchange="Patient.copyIdentiteAssureValues(this)" class="autocomplete"}}
                <div style="display:none;" class="autocomplete" id="_pays_naissance_insee_auto_complete"></div>
              {{/if}}
            {{/me_form_field}}

            {{me_form_bool mb_object=$patient mb_field="vip"}}
              {{mb_field object=$patient field="vip" typeEnum="checkbox"}}
            {{/me_form_bool}}

            {{me_form_bool mb_object=$patient mb_field="_douteux"}}
              {{mb_field object=$patient field="_douteux" typeEnum="checkbox"}}
            {{/me_form_bool}}

            {{me_form_bool mb_object=$patient mb_field="_fictif"}}
              {{mb_field object=$patient field="_fictif" typeEnum="checkbox"}}
            {{/me_form_bool}}
        </div>
      </div>
    </div>

    <div class="me-categorie-form adresse">
      <div class="categorie-form_titre">
        Coordonnées et contact
      </div>
      <div class="categorie-form_fields">
        <div class="categorie-form_fields-group">
            {{me_form_field mb_object=$patient mb_field="adresse"}}
            {{mb_field object=$patient field="adresse" onchange="Patient.copyAssureValues(this)"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="cp"}}
            {{mb_field object=$patient field="cp" onchange="Patient.copyAssureValues(this)"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="ville"}}
            {{mb_field object=$patient field="ville" onchange="Patient.copyAssureValues(this)"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="pays"}}
            {{mb_field object=$patient field="pays" size="31" onchange="Patient.copyAssureValues(this)" class="autocomplete"}}
              <div style="display:none;" class="autocomplete" id="pays_auto_complete"></div>
            {{/me_form_field}}
        </div>

        <div class="categorie-form_fields-group">
            {{me_form_field mb_object=$patient mb_field="phone_area_code"}}
            {{mb_field object=$patient field=phone_area_code}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="tel"}}
            {{mb_field object=$patient field="tel" onchange="Patient.copyAssureValues(this)" onkeyup="Patient.checkNotMobilePhone(this);"}}
              <div class="warning" id="phoneFormat"
                   style="display: none;">{{tr}}CPatient-alert-Warning this looks like a mobile phone number{{/tr}}</div>
            {{/me_form_field}}

            {{me_form_field layout=true mb_object=$patient mb_field="tel2" field_class="me-no-border me-padding-0"}}
            {{mb_field object=$patient field="tel2" onchange="Patient.copyAssureValues(this);" onkeyup="Patient.checkMobilePhone(this);"}}
            {{mb_field object=$patient field="allow_sms_notification" typeEnum='checkbox'}}{{mb_label object=$patient field="allow_sms_notification"}}
              <div class="warning" id="mobilePhoneFormat"
                   style="display: none;">{{tr}}CPatient-alert-Warning this does not look like a mobile phone number{{/tr}}</div>
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="tel_pro"}}
            {{mb_field object=$patient field="tel_pro"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="tel_autre"}}
            {{mb_field object=$patient field="tel_autre"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="tel_autre_mobile"}}
            {{mb_field object=$patient field=tel_autre_mobile}}
            {{/me_form_field}}

            {{me_form_field layout=true mb_object=$patient mb_field="email" field_class="me-no-border me-padding-0"}}
            {{mb_field object=$patient field="email"}}
            {{mb_field object=$patient field="allow_email" typeEnum='checkbox'}}{{mb_label object=$patient field="allow_email"}}
            {{/me_form_field}}
        </div>
      </div>
    </div>
  </div>

  <div class="me-list-categories">

    <div class="me-categorie-form situation">
      <div class="categorie-form_titre">
        Situation
      </div>
      <div class="categorie-form_fields">
        <div class="categorie-form_fields-group">
            {{me_form_field mb_object=$patient mb_field="situation_famille"}}
            {{mb_field object=$patient field="situation_famille"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="mdv_familiale"}}
            {{mb_field object=$patient field=mdv_familiale
            style="width: 12em;" emptyLabel="CPatient.mdv_familiale."}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="condition_hebergement"}}
            {{mb_field object=$patient field=condition_hebergement
            style="width: 12em;" emptyLabel="CPatient.condition_hebergement."}}
            {{/me_form_field}}
        </div>

        <div class="categorie-form_fields-group">
            {{me_form_field mb_object=$patient mb_field="niveau_etudes"}}
            {{mb_field object=$patient field=niveau_etudes
            style="width: 12em;" emptyLabel="CPatient.niveau_etudes."}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="activite_pro"}}
            {{mb_field object=$patient field=activite_pro
            style="width: 12em;" emptyLabel="CPatient.activite_pro." onchange="Patient.toggleActivitePro(this.value);"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="activite_pro_date"}}
            {{mb_field object=$patient field=activite_pro_date register=true form=editFrm}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="activite_pro_rques"}}
            {{mb_field object=$patient field=activite_pro_rques}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="profession"}}
            {{mb_field object=$patient field="profession" form=editFrm onchange="Patient.copyIdentiteAssureValues(this)" autocomplete="true,2,30,true,true,2"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="csp"}}
              <input type="text" name="_csp_view" size="25" value="{{$patient->_csp_view}}" />
            {{mb_field object=$patient field="csp" hidden=true}}
              <button type="button" class="cancel notext me-tertiary me-dark"
                      onclick="$V(this.form.elements['csp'], ''); $V(this.form.elements['_csp_view'], '');">{{tr}}Empty{{/tr}}</button>
            {{/me_form_field}}

            {{me_form_bool layout=true mb_object=$patient mb_field="fatigue_travail"}}
              {{mb_field object=$patient field=fatigue_travail default=""}}
            {{/me_form_bool}}

            {{me_form_field  mb_object=$patient mb_field="travail_hebdo"}}
            {{mb_field object=$patient field=travail_hebdo}}
            {{/me_form_field}}

            {{me_form_field  mb_object=$patient mb_field="transport_jour"}}
            {{mb_field object=$patient field=transport_jour}}
            {{/me_form_field}}
        </div>
      </div>
    </div>
    <div class="me-categorie-form assurance">
      <div class="categorie-form_titre">
        Assurance
      </div>
      <div class="categorie-form_fields">
        <div class="categorie-form_fields-group">
            {{if $conf.ref_pays == 2}}
                {{me_form_field mb_object=$patient mb_field="avs"}}
                {{mb_field object=$patient field="avs"}}
                {{/me_form_field}}
            {{else}}
              {{me_form_field mb_object=$patient mb_field="matricule"}}
                {{if $readonly}}
                  <div class="me-field-content">
                    {{mb_value object=$patient field="matricule"}}
                  </div>
                {{else}}
                  {{mb_field object=$patient field="matricule" onchange="Patient.copyIdentiteAssureValues(this)"}}
                {{/if}}
              {{/me_form_field}}
            {{/if}}

            {{me_form_field mb_object=$patient mb_field="qual_beneficiaire"}}
              {{mb_field object=$patient field="qual_beneficiaire" style="width:20em;"}}
            {{/me_form_field}}

            {{me_form_field layout=true mb_object=$patient mb_field="tutelle"}}
            {{mb_field object=$patient field="tutelle" typeEnum=radio default=$patient->tutelle onchange="Patient.refreshInfoTutelle(this.value);"}}
            {{/me_form_field}}
        </div>
      </div>
    </div>
    <div class="me-categorie-form info-sup">
      <div class="categorie-form_titre">
        Informations supplémentaires
      </div>
      <div class="categorie-form_fields">
        <div class="categorie-form_fields-group">
            {{me_form_field layout=true mb_object=$patient mb_field="don_organes"}}
              {{mb_field object=$patient field="don_organes" typeEnum=radio}}
            {{/me_form_field}}

            {{me_form_field layout=true mb_object=$patient mb_field="directives_anticipees" field_class="td-directives-anticipees"}}
              {{assign var=display_warning value=0}}
              {{if $patient->directives_anticipees == 1}}
                  {{if is_countable($patient->_refs_directives_anticipees) && $patient->_refs_directives_anticipees|@count == 0}}
                      {{assign var=display_warning value=1}}
                  {{/if}}
              {{/if}}
              {{mb_field object=$patient field="directives_anticipees" typeEnum=radio onchange="Patient.checkAdvanceDirectives(this, '$display_warning');"}}

              {{if $patient->directives_anticipees == 1}}
                <button type="button" class="search notext me-tertiary" title="{{tr}}CDirectiveAnticipee-action-See advance directive|pl{{/tr}}"
                        onclick="Patient.showAdvanceDirectives();" tabindex="1000"></button>
              {{/if}}

              {{if $display_warning}}
                <i class="fas fa-exclamation-triangle no-directives" style="color: #ff9502; font-size: 14px" title="{{tr}}CDirectiveAnticipee-No directive{{/tr}}"></i>
              {{/if}}
            {{/me_form_field}}

            {{if "terreSante"|module_active}}
              {{mb_include module=terreSante template=inc_checkbox_consent patient=$patient}}
            {{/if}}

            {{me_form_field mb_object=$patient mb_field="rques"}}
            {{mb_field object=$patient field="rques"}}
            {{/me_form_field}}
        </div>
        {{if $conf.dPpatients.CPatient.function_distinct}}
          <div class="categorie-form_fields-group">
            <button type="button" class="search me-tertiary"
                    onclick="Patient.accessibilityData();">{{tr}}CPatient-accessibility_data{{/tr}}</button>
          </div>
        {{/if}}
        {{if "sisra"|module_active}}
          <div class="categorie-form_fields-group">
            {{me_form_bool mb_object=$patient mb_field="allow_sisra_send"}}
              {{mb_field object=$patient field="allow_sisra_send"}}
            {{/me_form_bool}}
          </div>
        {{/if}}

        {{if "covercard"|module_active}}
          <div class="categorie-form_fields-group">
            {{me_form_field mb_object=$patient mb_field="_assureCC_id"}}
              {{mb_field object=$patient field="_assureCC_id"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="_assurance_assure_id"}}
              {{mb_field object=$patient field="_assurance_assure_id"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="_assure_end_date"}}
              {{mb_field object=$patient field="_assure_end_date"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="_assuranceCC_name"}}
              {{mb_field object=$patient field="_assuranceCC_name"}}
            {{/me_form_field}}

            {{me_form_field mb_object=$patient mb_field="_assuranceCC_id"}}
              {{mb_field object=$patient field="_assuranceCC_id"}}
            {{/me_form_field}}

            {{me_form_bool mb_object=$patient mb_field="_invalid_assurance"}}
              {{mb_field object=$patient field="_invalid_assurance"}}
            {{/me_form_bool}}
          </div>
        {{/if}}

        {{if $functions|@count > 1}}
          <div class="categorie-form_fields-group">
            {{me_form_field label="CPatient-Cabinet choice"}}
              <select name="function_id" onchange="$V(getForm('editFrm').function_id, this.value);">
                  {{mb_include module=mediusers template=inc_options_function list=$functions selected=$patient->function_id}}
              </select>
            {{/me_form_field}}
          </div>
        {{/if}}
        {{if "provenance"|module_active && 'Ox\Core\CAppUI::isGroup'|static_call:null}}
          {{mb_include module=provenance template=inc_edit_provenance_patient_v2}}
        {{/if}}
      </div>
    </div>
  </div>
</div>
