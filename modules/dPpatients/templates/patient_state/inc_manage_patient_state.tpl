{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    Control.Tabs.create('tabs-patient_state', true, {
      afterChange: function (container) {
        PatientState.getListPatientByState(container.id.replace(/patient_/, ''));
      }
    });
    Control.Tabs.setTabCount('patient_prov', {{$patients_count.vide}});
    Control.Tabs.setTabCount('patient_prov', {{$patients_count.prov}});
    Control.Tabs.setTabCount('patient_dpot', {{$patients_count.dpot}});
    Control.Tabs.setTabCount('patient_anom', {{$patients_count.anom}});
    Control.Tabs.setTabCount('patient_cach', {{$patients_count.cach}});
    Control.Tabs.setTabCount('patient_vali', {{$patients_count.vali}});
  });
</script>

<form name="filter_patient_state" method="post" onsubmit="return PatientState.filterPatientState(this)">
  <table class="form">
    <tr>
      <th style="width: 15%">{{tr}}CPatientState-_date_min{{/tr}}</th>
      <td class="text">
        {{mb_field class=CPatientState field=_date_min register=true form="filter_patient_state"
        prop=dateTime value="$date_min"}}
        <b>&raquo;</b>
        {{mb_field class=CPatientState field=_date_max register=true form="filter_patient_state"
        prop=dateTime value="$date_max"}}
      </td>
    </tr>

    <tr>
      <td colspan="2">
        <button type="submit" class="search">{{tr}}Filter{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>
<br />

<ul id="tabs-patient_state" class="control_tabs">
  <li><a href="#patient_vide">{{tr}}CPatientState.state.VIDE{{/tr}}</a></li>
  <li><a href="#patient_prov">{{tr}}CPatientState.state.PROV{{/tr}}</a></li>
  <li><a href="#patient_anom">{{tr}}CPatientState.state.ANOM{{/tr}}</a></li>
  <li><a href="#patient_cach">{{tr}}CPatientState.state.CACH{{/tr}}</a></li>
  <li><a href="#patient_vali">{{tr}}CPatientState.state.VALI{{/tr}}</a></li>
  <li><a href="#patient_dpot">{{tr}}CPatientState.state.DPOT{{/tr}}</a></li>
</ul>

<div id="patient_vide" style="display: none;"></div>
<div id="patient_prov" style="display: none;"></div>
<div id="patient_anom" style="display: none;"></div>
<div id="patient_cach" style="display: none;"></div>
<div id="patient_vali" style="display: none;"></div>
<div id="patient_dpot" style="display: none;"></div>