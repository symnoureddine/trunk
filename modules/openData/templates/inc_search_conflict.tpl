{{*
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=openData script=import_medecins ajax=1}}

{{if $change_page}}
  {{mb_include module=system template=inc_pagination change_page='ImportMedecins.changePage' total=$total current=$start step=$step change_page_arg=$audit}}
{{/if}}

<form name="handle-conflicts" method="get">
  <input type="hidden" name="audit" value="{{$audit}}"/>
  <table class="main tbl">
    <tr>
      <td colspan="7" align="right">
        <button type="button" class="tick" onclick="ImportMedecins.handleAllConflict('{{$medecins_ids}}')">
          {{tr}}CMedecinImport-handle-all{{/tr}}
        </button>
      </td>
    </tr>
    {{foreach from=$medecins key=_id item=_medecin}}
      <input type="hidden" name="fields-{{$_id}}" value="{{$fields[$_id]}}"/>
      <input type="hidden" name="file_version_{{$_id}}" value="{{$_medecin->import_file_version}}"
      <tr>
        <th>{{tr}}CImportConflict-praticien{{/tr}}</th>
        <th>{{tr}}CImportConflict-field-name{{/tr}}</th>
        <th style="width: 33%;">{{tr}}CImportConflict-value-mediboard{{/tr}}</th>
        {{if !$audit}}
          <th class="narrow">
            <input type="radio" name="check-all-{{$_id}}" onchange="ImportMedecins.checkAllValues('{{$_id}}', 'old')" checked/>
          </th>
          <th class="narrow">
            <input type="radio" name="check-all-{{$_id}}" onchange="ImportMedecins.checkAllValues('{{$_id}}', 'new')"/>
          </th>
        {{/if}}
        <th style="width: 33%;">{{tr}}CImportConflict-value-csv{{/tr}}</th>
        <th>{{tr}}CImportConflict-conflict-choice{{/tr}}</th>
      </tr>
      {{assign var=idx value=0}}
      {{foreach from=$conflicts[$_id] item=_conflict}}
        {{assign var=field value=$_conflict->field}}
        <tr>
          {{if $idx < 1}}
            <td align="center" rowspan="{{$conflicts[$_id]|@count}}">
              <span onmouseover="ObjectTooltip.createEx(this, '{{$_medecin->_guid}}')">{{$_medecin}}</span>
            </td>
          {{/if}}

          <td align="center">{{tr}}CMedecin-{{$field}}{{/tr}}</td>
          <td align="center">{{$_medecin->$field}}</td>

          {{if !$audit}}
            <td class="narrow" align="center">
              <input type="radio" class="medecin-{{$_id}}" name="medecin-{{$_id}}-{{$field}}" value="old" checked/>
            </td>
            <td class="narrow" align="center">
              <input type="radio" class="medecin-{{$_id}}" name="medecin-{{$_id}}-{{$field}}" value="new"/>
            </td>
          {{/if}}

          <td align="center">
            <input type="hidden" name="medecin-{{$_id}}-{{$field}}-value" value="{{$_conflict->value}}"/>
            {{$_conflict->value}}
          </td>

          {{if $idx < 1}}
            <td align="center" class="text" rowspan="{{$conflicts[$_id]|@count}}" id="action-picker-{{$_id}}">
              {{if $perm->admin == 1}}
                <input type="checkbox" name="medecin-ignore-rpps-{{$_id}}" value="1"/>
                <label for="medecin-ignore-rpps-{{$_id}}">{{tr}}CMedecinImport-ignore-rpps{{/tr}}</label>
              {{/if}}

              <br/>

              <button type="button" class="tick" onclick="ImportMedecins.handleConflictResolution('{{$_id}}')">
                {{tr}}Validate{{/tr}}
              </button>
            </td>
          {{/if}}
        </tr>
        {{assign var=idx value=$idx+1}}
      {{/foreach}}
    {{/foreach}}
  </table>
</form>