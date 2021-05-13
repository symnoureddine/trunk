{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="tbl">
  <tr>
    <th class="title" colspan="2">{{tr}}CSourceIdentite-INS actif{{/tr}}</th>
  </tr>
  <tr>
    <th>
      {{mb_label class=CPatientINSNIR field=oid}}
    </th>
    <th>
      {{mb_label class=CPatient field=matricule}}
    </th>
  </tr>

  <tr>
    <td>
      {{mb_value object=$source_identite->_ref_patient_ins_nir field=oid}}
    </td>
    <td>
      {{mb_value object=$source_identite->_ref_patient_ins_nir field=ins_nir}}
    </td>
  </tr>

  {{if $source_identite->_ref_patients_ins_nir}}
    <tr>
      <th class="title" colspan="2">{{tr}}CSourceIdentite-INS historized{{/tr}}</th>
    </tr>

    <tr>
      <th>
        {{mb_label class=CPatientINSNIR field=oid}}
      </th>
      <th>
        {{mb_label class=CPatient field=matricule}}
      </th>
    </tr>

    {{foreach from=$source_identite->_ref_patients_ins_nir item=_patient_ins_nir}}
    <tr>
      <td>
        {{mb_value object=$_patient_ins_nir field=oid}}
      </td>
      <td>
        {{mb_value object=$_patient_ins_nir field=ins_nir}}
      </td>
    </tr>
    {{/foreach}}
  {{/if}}
</table>
