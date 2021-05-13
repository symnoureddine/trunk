{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="editFrm" action="?m={{$m}}" method="post" onsubmit="return BanqueEdit.save(this)">
  {{mb_class object=$banque}}
  {{mb_key object=$banque}}
  <input type="hidden" name="del" value="0" />
  <table class="form">
    {{mb_include module=system template=inc_form_table_header object=$banque}}
    <tr>
      <th>{{mb_label object=$banque field="nom"}}</th>
      <td>{{mb_field object=$banque field="nom"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$banque field="description"}}</th>
      <td>{{mb_field object=$banque field="description"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$banque field="departement"}}</th>
      <td>{{mb_field object=$banque field="departement"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$banque field="boite_postale"}}</th>
      <td>{{mb_field object=$banque field="boite_postale"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$banque field="adresse"}}</th>
      <td>{{mb_field object=$banque field="adresse"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$banque field="cp"}}</th>
      <td>{{mb_field object=$banque field="cp"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$banque field="ville"}}</th>
      <td>{{mb_field object=$banque field="ville"}}</td>
    </tr>
    <tr>
      <td class="button" colspan="2">
        <button class="modify" type="submit">{{tr}}Save{{/tr}}</button>
        {{if $banque->_id}}
          <button class="trash" type="button"
              onclick="BanqueEdit.delete(this.form,{typeName:'la banque ',objName:'{{$banque->nom|smarty:nodefaults|JSAttribute}}'})">
            {{tr}}Delete{{/tr}}
          </button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>