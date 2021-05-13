{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if !$object->_can->read}}
  <div class="small-info">
    {{tr}}{{$object->_class}}{{/tr}} : {{tr}}access-forbidden{{/tr}}
  </div>
  {{mb_return}}
{{/if}}

{{assign var="medecin" value=$object}}
<table class="tbl tooltip">
  <tr>
    <th class="title text" colspan="2">
      {{mb_include module=system template=inc_object_idsante400 object=$medecin}}
      {{mb_include module=system template=inc_object_history object=$medecin}}
      {{mb_include module=system template=inc_object_notes object=$medecin}}
      {{$medecin}}
    </th>
  </tr>
  {{if $medecin->disciplines}}
    <tr>
      <td colspan="2">
        <strong>{{mb_value object=$medecin field="disciplines"}}</strong>
      </td>
    </tr>
  {{/if}}
  <tr>
    <td style="width: 50%;">
      {{mb_label object=$medecin field="tel"}} :
      {{mb_value object=$medecin field="tel"}}
    </td>
    <td>{{mb_value object=$medecin field="adresse"}}</td>
  </tr>
  <tr>
    <td>
      {{mb_label object=$medecin field="fax"}} :
      {{mb_value object=$medecin field="fax"}}
    </td>
    <td>{{mb_value object=$medecin field="cp"}} {{mb_value object=$medecin field="ville"}}</td>
  </tr>
  <tr>
    <td>
      {{mb_label object=$medecin field="portable"}} :
      {{mb_value object=$medecin field="portable"}}
    </td>
    <td>
      {{mb_label object=$medecin field="email"}} :
      {{mb_value object=$medecin field="email"}}
    </td>
  </tr>
  <tr>
    <td>
      {{mb_label object=$medecin field="rpps"}} :
      {{mb_value object=$medecin field="rpps"}}
    </td>
    <td>
      {{mb_label object=$medecin field="adeli"}} :
      {{mb_value object=$medecin field="adeli"}}
    </td>
  </tr>
  {{if $medecin->orientations}}
    <tr>
      <td colspan="2">
        {{mb_value object=$medecin field="orientations"}}
      </td>
    </tr>
  {{/if}}
  {{if $medecin->complementaires}}
    <tr>
      <td colspan="2">
        {{mb_value object=$medecin field="complementaires"}}
      </td>
    </tr>
  {{/if}}
  {{if $object->_can->edit && ($object->_ref_module->_can->admin || !"dPpatients CMedecin edit_for_admin"|gconf)}}
    <tr>
      <td colspan="2" class="button">
        {{mb_script module="dPpatients" script="medecin" ajax="true"}}
        <button type="button" class="edit" onclick="Medecin.editMedecin('{{$medecin->_id}}')">
          {{tr}}Modify{{/tr}}
        </button>
      </td>
    </tr>
  {{/if}}
</table>