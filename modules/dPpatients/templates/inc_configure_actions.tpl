{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  var Actions = {
    civilite: function (mode) {
      if (mode == "repair") {
        if (!confirm("Etes-vous sur de vouloir réparer les civilités ?")) {
          return;
        }
      }
      var url = new Url("dPpatients", "ajax_civilite");
      url.addParam("mode", mode);
      url.requestUpdate("ajax_civilite");
    },

    patientState: function (action) {
      var state = $$("input:checked[type=radio][name=state]")[0].value;
      new Url("dPpatients", "ajax_patient_state_tools")
        .addParam("action", action)
        .addParam("state", state)
        .requestUpdate("result_tools_patient_state");
    }
  };
  editAntecedent = function (mode) {
    var url = new Url('patients', 'ajax_check_dossier');
    url.addParam("mode", mode);
    if (mode == "repair") {
      if (!confirm("Etes-vous sur de vouloir réparer les dossier médicaux ?")) {
        return;
      }
      url.requestUpdate("list_doublons_dossier_medicaux");
    } else {
      url.requestModal('40%');
    }
  };
  changePageDoublonDossier = function (page) {
    var url = new Url('patients', 'ajax_check_dossier');
    url.addParam("page", page);
    url.addParam("mode", 'check');
    url.requestUpdate("list_doublons_dossier_medicaux");
  };
  Main.add(function () {
    Control.Tabs.create('tabs-actions', true);
  });
</script>

<table>
  <tr>
    <td style="vertical-align: top;">
      <ul id="tabs-actions" class="control_tabs_vertical small">
        <li><a href="#CPatient-maintenance">{{tr}}CPatient{{/tr}}</a></li>
        <li><a href="#CMedecin-maintenance">{{tr}}CMedecin{{/tr}}</a></li>
        <li><a href="#CCorrespondantPatient-maintenance">{{tr}}CCorrespondantPatient{{/tr}}</a></li>
        <li><a href="#INSEE-maintenance">{{tr}}INSEE{{/tr}}</a></li>
        <li><a href="#INSC-maintenance">{{tr}}CPatient-INSC{{/tr}}</a></li>
        <li><a href="#constantes-maintenance">{{tr}}CConstantesMedicales{{/tr}}</a></li>
      </ul>
    </td>
    <td style="vertical-align: top; width: 100%">
      <div id="CPatient-maintenance" style="display: none;">
        {{mb_include template=CPatient_maintenance}}
      </div>

      <div id="CMedecin-maintenance" style="display: none;">
        {{mb_include template=CMedecin_maintenance}}
      </div>

      <div id="CCorrespondantPatient-maintenance" style="display: none;">
        {{mb_include template=CCorrespondantPatient_maintenance}}
      </div>

      <div id="INSEE-maintenance" style="display: none;">
        {{mb_include template=INSEE_maintenance}}
      </div>

      <div id="INSC-maintenance" style="display: none;">
        {{mb_include template="ins/insc_maintenance"}}
      </div>

      <div id="constantes-maintenance" style="display: none;">
        {{mb_include template=CConstantesMedicales_maintenance}}
      </div>
    </td>
  </tr>
</table>