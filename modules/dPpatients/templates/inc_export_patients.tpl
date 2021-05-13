{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  nextStepPatients = function () {
    var form = getForm("export-patients-form");
    $V(form.start, parseInt($V(form.start)) + parseInt($V(form.step)));

    if ($V(form.auto)) {
      form.onsubmit();
    }
  };

  checkDirectory = function (input) {
    var url = new Url("patients", "ajax_check_export_dir");
    url.addParam("directory", $V(input));
    url.requestUpdate("directory-check");
  };

  Main.add(function () {
    var patientForm = getForm("export-patients-form");
    Calendar.regField(patientForm.date_min);
    Calendar.regField(patientForm.date_max);
  });
</script>

<div class="small-info">
  <ul>
    <li>{{tr}}dPpatients-export-Infos 1{{/tr}}</li>
    <li>{{tr}}dPpatients-export-Infos 2{{/tr}}</li>
    <li>{{tr}}dPpatients-export-Infos 3{{/tr}}</li>
    <li>{{tr}}dPpatients-export-Infos 4{{/tr}}</li>
  </ul>
</div>

<form name="export-patients-form" method="post" onsubmit="return onSubmitFormAjax(this, {useDollarV: true}, 'export-log-patients')">
  <input type="hidden" name="m" value="patients" />
  <input type="hidden" name="dosql" value="do_export_patients" />

  <select name="praticien_id[]" multiple style="display: none;">
    {{foreach from=$praticiens item=_prat}}
      <option value="{{$_prat->_id}}">{{$_prat}}</option>
    {{/foreach}}
  </select>

  <table class="main form">
    <tr>
      <th class="section" colspan="6">{{tr}}dPpatients-export-Basic infos{{/tr}}</th>
    </tr>

    <tr>
      <th>
        <label for="directory">{{tr}}dPpatients-export-Target directory{{/tr}}</label>
      </th>
      <td colspan="5">
        <input type="text" name="directory" value="{{$directory}}" size="60" onchange="checkDirectory(this)" />
        <div id="directory-check"></div>
      </td>
    </tr>

    <tr>
      <th>
        <label for="directory_name">{{tr}}dPpatients-export-Directory name{{/tr}}</label>
      </th>
      <td colspan="5">
        <input type="text" name="directory_name" value="{{$directory}}" size="30"/>
      </td>
    </tr>

    <tr>
      <th class="narrow">
        <label for="start">{{tr}}Start{{/tr}}</label>
      </th>
      <td class="narrow">
        <input type="text" name="start" value="{{$start}}" size="4" />
      </td>

      <th class="narrow">
        <label for="step">{{tr}}Step{{/tr}}</label>
      </th>
      <td class="narrow">
        <input type="text" name="step" value="{{$step}}" size="4" />
      </td>

      <td colspan="2"></td>
    </tr>

    <tr>
      <th class="narrow">
        <label for="auto">{{tr}}Auto{{/tr}}</label>
      </th>
      <td>
        <input type="checkbox" name="auto" value="1" />
      </td>

      <th class="narrow">
        <label for="update">{{tr}}dPpatients-export-Update export files{{/tr}}</label>
      </th>
      <td colspan="3">
        <input type="checkbox" name="update" value="1" {{if $update}}checked{{/if}}/>
      </td>
    </tr>

    <tr>
      <th class="section" colspan="6">{{tr}}dPpatients-export-Files infos{{/tr}}</th>
    </tr>

    <tr>
      <th>
        <label for="ignore_files">{{tr}}dPpatients-export-Do not copy files{{/tr}}</label>
      </th>
      <td>
        <input type="checkbox" name="ignore_files" value="1" {{if $ignore_files}}checked{{/if}} />
      </td>

      <th>
        <label for="generate_pdfpreviews">{{tr}}dPpatients-export-Force cr pdf generation{{/tr}}</label>
      </th>
      <td colspan="3">
        <input type="checkbox" name="generate_pdfpreviews" value="1" {{if $generate_pdfpreviews}}checked{{/if}} />
      </td>
    </tr>

    <tr>
      <th class="section" colspan="6">
        {{tr}}dPpatients-export-Date options{{/tr}}
      </th>
    </tr>

    <tr>
      <th>
        <label for="date_min">{{tr}}dPpatients-export-Date min{{/tr}}</label>
      </th>
      <td>
        <input type="hidden" name="date_min" value="{{$date_min}}" />
      </td>

      <th>
        <label for="date_max">{{tr}}dPpatients-export-Date max{{/tr}}</label>
      </th>
      <td colspan="3">
        <input type="hidden" name="date_max" value="{{$date_max}}" />
      </td>
    </tr>

    <tr>
      <th colspan="6" class="section">{{tr}}dPpatients-export-Other options{{/tr}}</th>
    </tr>

    <tr>
      <th>
        <label for="all_prats">Tous les praticiens de <strong>l'instance</strong></label>
      </th>
      <td>
        <input type="checkbox" name="all_prats" value="1" {{if $all_prats}}checked{{/if}} />
      </td>

      <th>
        <label for="use_function">Mode TAMM</label>
      </th>
      <td colspan="3">
        <input type="checkbox" name="use_function" value="1" {{if $use_function}}checked{{/if}}/>
      </td>
    </tr>

    <tr>
      <th>
        <label for="patient_infos"
               title="{{tr}}dPpatients-export-Informations patient{{/tr}}">{{tr}}dPpatients-export-patient infos{{/tr}}</label>
      </th>
      <td>
        <input type="checkbox" name="patient_infos" value="1" {{if $patient_infos}}checked{{/if}}/>
      </td>

      <th>
        <label for="patient_id">Patient Id</label>
      </th>
      <td colspan="3">
        <input type="text" size="5" name="patient_id" value="{{$patient_id}}" />
      </td>
    </tr>

    <tr>
      <th>
        <label for="consult_only">{{tr}}dPpatients-export-Consult only{{/tr}}</label>
      </th>
      <td>
        <input type="checkbox" name="consult_only" value="1" />
      </td>

      <th>
        <label for="sejour_only">{{tr}}dPpatients-export-Sejour only{{/tr}}</label>
      </th>
      <td colspan="3">
        <input type="checkbox" name="sejour_only" value="1" />
      </td>
    </tr>

    <tr>
      <th colspan="6" class="section">{{tr}}dPpatients-export-Archive options{{/tr}}</th>
    </tr>

    <tr>
      <th>
        <label for="archive_sejour">{{tr}}dPpatients-export-Archive sejours{{/tr}}</label>
      </th>
      <td>
        <input type="checkbox" name="archive_sejour" value="1" />
      </td>

      <th>
        <label for="archive_mode">{{tr}}dPpatients-export-Archive mode{{/tr}}</label>
      </th>
      <td colspan="3">
        <input type="checkbox" name="archive_mode" value="1" />
      </td>
    </tr>


    <tr>
      <td colspan="6" class="button">
        <button class="fas fa-external-link-alt">{{tr}}CPatient-action Export XML{{/tr}}</button>
      </td>
    </tr>
  </table>

</form>

<div id="export-log-patients"></div>