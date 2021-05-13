{{*
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    IdInterpreter.init(getForm('idinterpreter-upload-file')['formfile[]'], '{{$patient->_guid}}');
  });
</script>
<div id="idinterpreter-loading" style="width: 99%; text-align: center; display:none;">
  <span>{{tr}}Loading in progress{{/tr}}</span>
</div>
<div id="idinterpreter-form">
  <form name="idinterpreter-upload-file" onsubmit="return IdInterpreter.submitImage(this);" method="post">
    <input type="hidden" name="m" value="files" />
    <input type="hidden" name="dosql" value="do_id_interpreter" />
    <table class="tbl">
      <tr>
        <td colspan="2">
          {{mb_include module=system template=inc_inline_upload lite=true multi=false paste=false}}

        </td>
      </tr>
      {{if $patient->_id}}
          <tr>
            <td colspan="2" style="text-align: center; padding: 4px;">
              <div class="inline-upload-header" onclick="IdInterpreter.showPatientFiles('{{$patient->_guid}}');">
                <label for="" class="inline-upload-input" style="right: 0%;">
                  <div class="inline-upload-input-text">
                    <i class="fas fa-file"></i>
                    {{tr}}CIdInterpreter.patient_uploaded_files{{/tr}}
                  </div>
                </label>
              </div>

              <div class="inline-upload-files" id="idinterpreter-self-img" style="display:none">
                <div class="inline-upload-file">
                  <div class="inline-upload-thumbnail">
                    <img src="" style="" />
                  </div>
                  <div class="inline-upload-info">
                    <button class="inline-upload-trash far fa-trash-alt notext" type="button"
                            onclick="IdInterpreter.resetPatientFile()">
                      {{tr}}Delete{{/tr}}
                    </button>
                  </div>
                </div>
              </div>
            </td>
          </tr>
      {{/if}}
      <tr>
        <td>
          <label for="file_type">{{tr}}CIdIterpreter-file_type{{/tr}}</label>
        </td>
        <td>
          <select name="file_type" id="file_type">
            <option value="id_card">{{tr}}CIdInterpreter.id_card{{/tr}}</option>
            <option value="passport">{{tr}}CIdInterpreter.passport{{/tr}}</option>
            <option value="residence_permit">{{tr}}CIdInterpreter.residence_permit{{/tr}}</option>
          </select>
        </td>
      </tr>
      <tr>
        <td class="button" colspan="2">
          <button type="button" class="upload" onclick="this.form.onsubmit();">{{tr}}Upload{{/tr}}</button>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <div class="info">
            {{tr}}CIdInterpreter.information{{/tr}}
          </div>
        </td>
      </tr>
    </table>
  </form>
</div>
<div id="idinterpreter-result" style="display:none">
  <form name="idinterpreter-result" method="get" onsubmit="return IdInterpreter.submitFields(this);">
    <table class="tbl form" style="width: 300px;">
      <tr>
        <th class="title" colspan="3">{{tr}}CIdInterpreter.extracted_data{{/tr}}</th>
        <th class="title">{{tr}}CIdInterpreter.initial_image{{/tr}}</th>
      </tr>
      <tr>
        <th class="narrow"></th>
        <th>Libellé</th>
        <th>Valeur</th>
        <td rowspan="9" id="idinterpreter-show-container">
          <img id="idinterpreter-show-file" style="max-height : 100%; max-width: 400px">
        </td>
      </tr>
      <tr>
        <td><input type="checkbox" name="patient_nom" value="nom" {{if $patient->_id}}checked disabled{{/if}}/></td>
        <td>{{mb_label class=CPatient field=nom}}</td>
        <td>{{mb_field class=CPatient field=nom canNull=true}}</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="patient_prenom" value="prenom" {{if $patient->_id}}checked disabled{{/if}}/></td>
        <td>{{mb_label class=CPatient field=prenom}}</td>
        <td>{{mb_field class=CPatient field=prenom canNull=true}}</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="patient_nom_jeune_fille" value="nom_jeune_fille" {{if $patient->_id}}checked disabled{{/if}}/></td>
        <td>{{mb_label class=CPatient field=nom_jeune_fille}}</td>
        <td>{{mb_field class=CPatient field=nom_jeune_fille canNull=true}}</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="patient_sexe" value="sexe" {{if $patient->_id}}checked disabled{{/if}}/></td>
        <td>{{mb_label class=CPatient field=sexe}}</td>
        <td>{{mb_field class=CPatient field=sexe canNull=true}}</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="patient_civilite" value="civilite" {{if $patient->_id}}checked disabled{{/if}}/></td>
        <td>{{mb_label class=CPatient field=civilite}}</td>
        <td>{{mb_field class=CPatient field=civilite canNull=true}}</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="patient_naissance" value="naissance" {{if $patient->_id}}checked disabled{{/if}}/></td>
        <td>{{mb_label class=CPatient field=naissance}}</td>
        <td>{{mb_field class=CPatient field=naissance canNull=true}}</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="source_date_fin_validite" value="_date_fin_validite" {{if $patient->_id}}checked disabled{{/if}}/></td>
        <td>{{mb_label class=CPatient field=_date_fin_validite}}</td>
        <td>{{mb_field class=CPatient field=_date_fin_validite canNull=true form=idinterpreter-result register=true}}</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="patient_image" value="image"/></td>
        <td>{{tr}}CMediusers-ID photo{{/tr}}</td>
        <td><img src="" alt="" style="width: 200px;" id="idinterpreter-image"/></td>
      </tr>
      <tr class="button">
        <td class="button" colspan="3">
          <button type="button" onclick="IdInterpreter.reset();" class="left">{{tr}}Back{{/tr}}</button>

          {{if $patient->_id}}
            <button class="save">{{tr}}Save{{/tr}}</button>
          {{else}}
            <button class="import">{{tr}}CIdInterpreter.report_data{{/tr}}</button>
          {{/if}}
        </td>
      </tr>
    </table>
  </form>
</div>

{{if $patient->_id}}
  <form name="idinterpreter-update-files" enctype="multipart/form-data" class="prepared"
        method="post" style="display:none">
    <input type="hidden" name="m" value="files" />
    <input type="hidden" name="a" value="upload_file" />
    <input type="hidden" name="dosql" value="do_file_aed" />
    <input type="hidden" name="object_class" value="{{$patient->_class}}" />
    <input type="hidden" name="object_id" value="{{$patient->_id}}" />
    <input type="hidden" name="named" value="1" />
    <div></div>
  </form>
{{/if}}
