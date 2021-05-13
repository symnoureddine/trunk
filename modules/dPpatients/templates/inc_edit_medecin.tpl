{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=patients script=salutation ajax=true}}

<script>
  checkSexAndTitle = function (form) {
    var sex_input = form.elements.sexe;
    var title_input = form.elements.titre;
    var type_input = form.elements.type;

    var sex = $V(sex_input);
    var type = $V(type_input);

    switch (sex) {
      case 'm':
        var list = ['mme'];
        if (type == 'medecin') {
          list.push('m');
        }

        disableOptions(title_input, list);
        break;

      case 'f':
        var list = ['m'];
        if (type == 'medecin') {
          list.push('mme');
        }

        disableOptions(title_input, list);
        break;

      default:
        $A(title_input.options).each(function (o) {
          o.disabled = null;
        });
    }

    if (type == 'medecin') {
      $V(title_input, 'dr');
    } else {
      switch (sex) {
        case 'm':
          $V(title_input, 'm');
          break;

        case 'f':
          $V(title_input, 'mme');
      }
    }
  };

  disableOptions = function (select, list) {
    $A(select.options).each(function (o) {
      o.disabled = list.include(o.value);
    });

    if (select.value == '' || select.options[select.selectedIndex].disabled) {
      selectFirstEnabled(select);
    }
  };

  selectFirstEnabled = function (select) {
    var found = false;

    $A(select.options).each(function (o, i) {
      if (!found && !o.disabled && o.value != '') {
        $V(select, o.value);
        found = true;
      }
    });
  };

  setFromUser = function (user_id) {
    var url = new Url('mediusers', 'ajax_get_user_infos');
    url.addParam('user_id', user_id);
    url.requestJSON(function (data) {
      var form = getForm('editMedecin_{{$object->_id}}');
      $V(form.nom, data.last_name);
      $V(form.prenom, data.name);
      $V(form.adresse, data.address);
      $V(form.cp, data.pc);
      $V(form.ville, data.city);
      $V(form.tel, data.phone);
      $V(form.email, data.email);
      $V(form.email_apicrypt, data.apicrypt);
      $V(form.mssante_address, data.mssante);
      $V(form.type, data.type);
      $V(form.adeli, data.adeli);
      $V(form.rpps, data.rpps);
    });
  };

  showConflicts = function (medecin_id) {
    var url = new Url('openData', 'ajax_search_medecin_conflict');
    url.addParam('medecin_id', medecin_id);
    url.requestModal('80%', '50%');
  };

  Main.add(function () {
    var form = getForm('editMedecin_{{$object->_id}}');
    // Autocomplete des users
    var url = new Url("mediusers", "ajax_users_autocomplete");
    url.addParam("praticiens", '1');
    url.addParam("input_field", '_user_view');
    url.autoComplete(form._user_view, null, {
      minChars:           0,
      method:             "get",
      select:             "view",
      dropdown:           true,
      afterUpdateElement: function (field, selected) {
        if ($V(form._user_view) == "") {
          $V(form._user_view, selected.down('.view').innerHTML);
        }

        var id = selected.getAttribute("id").split("-")[2];
        $V(form.user_id, id, true);
        {{if !$object->_id}}
        setFromUser($V(form.user_id));
        {{/if}}
      }
    });
  });
</script>

<form method="post" name="editMedecin_{{$object->_id}}" onsubmit="return onSubmitFormAjax(this, Control.Modal.close)">
  {{mb_class object=$object}}
  {{mb_key object=$object}}

  <table class="main form me-small-form">
    {{mb_include module=system template=inc_form_table_header}}

    <tr>
      <th>{{mb_label object=$object field=nom}}</th>
      <td>{{mb_field object=$object field=nom style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=prenom}}</th>
      <td>{{mb_field object=$object field=prenom style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=jeunefille}}</th>
      <td>{{mb_field object=$object field=jeunefille style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=sexe}}</th>
      <td>
        {{if $object->titre == ''}}
          {{mb_field object=$object field=sexe onchange="checkSexAndTitle(this.form);"}}
        {{else}}
          {{mb_field object=$object field=sexe}}
        {{/if}}
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=user_id}}</th>
      <td>
        {{mb_field object=$object field=user_id hidden=true}}
        <input type="text" name="_user_view" value="{{$object->_ref_user}}" />
        <button type="button" class="cancel notext"
                onclick="$V(getForm('editMedecin_{{$object->_id}}')._user_view, ''); $V(getForm('editMedecin_{{$object->_id}}').user_id, '');"></button>
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=titre}}</th>
      <td>
        {{assign var=titre_locales value=$object->_specs.titre}}
        <select name="titre">
          <option value="">&mdash; {{tr}}CMedecin-titre.select{{/tr}}</option>
          {{foreach from=$titre_locales->_locales key=key item=_titre}}
            <option value="{{$key}}" {{if $key == $object->titre}}selected{{/if}}>
              {{tr}}CMedecin.titre.{{$key}}-long{{/tr}} &mdash; ({{$_titre}})
            </option>
          {{/foreach}}
        </select>
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=adresse}}</th>
      <td>{{mb_field object=$object field=adresse}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=cp}} {{mb_label object=$object field=ville}}</th>
      <td>{{mb_field object=$object field=cp}} {{mb_field object=$object field=ville}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=tel}}</th>
      <td>{{mb_field object=$object field=tel style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=fax}}</th>
      <td>{{mb_field object=$object field=fax style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field="tel_autre"}}</th>
      <td>{{mb_field object=$object field="tel_autre"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=portable}}</th>
      <td>{{mb_field object=$object field=portable style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=email}}</th>
      <td>{{mb_field object=$object field=email style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=email_apicrypt}}</th>
      <td>{{mb_field object=$object field=email_apicrypt style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=mssante_address}}</th>
      <td>{{mb_field object=$object field=mssante_address style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=type}}</th>
      <td>
        {{if $object->titre == ''}}
          {{mb_field object=$object field=type onchange="checkSexAndTitle(this.form);" style="width: 13em;"}}
        {{else}}
          {{mb_field object=$object field=type style="width: 13em;"}}
        {{/if}}
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=disciplines}}</th>
      <td>{{mb_field object=$object field=disciplines}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=spec_cpam_id}}</th>
      <td>{{mb_include module=mediusers template=inc_select_cpam_speciality field=spec_cpam_id selected=$object->spec_cpam_id specialities=$spec_cpam width="250px" empty_value=true}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=orientations}}</th>
      <td>{{mb_field object=$object field=orientations}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=complementaires}}</th>
      <td>{{mb_field object=$object field=complementaires}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=rpps}}</th>
      <td>{{mb_field object=$object field=rpps style="width: 13em;"}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=adeli}}</th>
      <td>{{mb_field object=$object field=adeli style="width: 13em;"}}</td>
    </tr>

    {{if "courrier"|module_active}}
      <tr>
        <th>{{mb_label object=$object field=modalite_publipostage}}</th>
        <td>{{mb_field object=$object field=modalite_publipostage emptyLabel=None|f}}</td>
      </tr>
    {{/if}}

    <tr>
      <th>{{mb_label object=$object field=actif}}</th>
      <td>{{mb_field object=$object field=actif}}</td>
    </tr>

    <tr>
      <th class="category" colspan="2">
        {{tr}}CMedecin-rpps options{{/tr}}
      </th>
    </tr>

    {{if $conflicts}}
      <tr>
        <td colspan="2">
          <div class="big-warning" style="text-align: center;">
            {{tr}}CImportConflict-medecin conflict exists{{/tr}}.
            <br />

            <button class="search" type="button" onclick="showConflicts('{{$object->_id}}');">
              {{tr}}CMedecin-display conflicts{{/tr}} ({{$conflicts|@count}})
            </button>
          </div>
        </td>
      </tr>
    {{/if}}

    <tr>
      <th>{{mb_label object=$object field=ignore_import_rpps}}</th>
      <td>{{mb_field object=$object field=ignore_import_rpps}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$object field=import_file_version}}</th>
      <td>{{mb_value object=$object field=import_file_version}}</td>
    </tr>

    {{if $object->_id}}
      <tr>
        <th class="category" colspan="2">{{tr}}CSalutation.mine{{/tr}}</th>
      </tr>
      <tr>
        <th>{{mb_label object=$object field=_starting_formula}}</th>
        <td class="text compact">{{mb_value object=$object field=_starting_formula style="width: 90%; box-sizing: border-box;"}}</td>
      </tr>
      <tr>
        <th>{{mb_label object=$object field=_closing_formula}}</th>
        <td class="text compact">{{mb_value object=$object field=_closing_formula style="width: 90%; box-sizing: border-box;"}}</td>
      </tr>
    {{/if}}

    <tr>
      <td class="button" colspan="2">
        {{if $object->_id}}
          <button class="save">{{tr}}Edit{{/tr}}</button>
          <button class="search" type="button" onclick="Salutation.manageSalutations('{{$object->_class}}', '{{$object->_id}}');">
            {{tr}}CSalutation-action-Manage salutations{{/tr}}
          </button>
          <button class="print notext" type="button" onclick="Medecin.viewPrint('{{$object->_id}}');">
            {{tr}}Print{{/tr}}
          </button>
          <button class="trash" type="button" onclick="confirmDeletion(this.form, {ajax: true}, Control.Modal.close)">
            {{tr}}Delete{{/tr}}
          </button>
        {{else}}
          <button class="save">{{tr}}Create{{/tr}}</button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>