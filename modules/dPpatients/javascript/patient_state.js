/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

PatientState = {

  filterPatientState: function (form) {
    new Url("dPpatients", "ajax_filter_patient_state")
      .addFormData(form)
      .requestUpdate("patient_manage");

    return false;
  },

  getListPatientByState: function (state, page) {
    new Url("dPpatients", "ajax_list_patient_state")
      .addParam("state", state)
      .addParam("page", page)
      .requestUpdate("patient_" + state);
  },

  edit_patient: function (patient_id, state) {
    Patient.editModal(patient_id, false, null, PatientState.getListPatientByState.curry(state))
  },

  changePage: {
    prov: function (page) {
      PatientState.getListPatientByState('prov', page);
    },

    vali: function (page) {
      PatientState.getListPatientByState('vali', page);
    },

    dpot: function (page) {
      PatientState.getListPatientByState('dpot', page);
    },

    anom: function (page) {
      PatientState.getListPatientByState('anom', page);
    },

    cach: function (page) {
      PatientState.getListPatientByState('cach', page);
    }
  },

  mergePatient: function (patients_id) {
    new Url("system", "object_merger")
      .addParam("objects_class", "CPatient")
      .addParam("objects_id", patients_id)
      .popup(800, 600, "merge_patients");
  },

  stats_filter: function (form) {
    var url = new Url("dPpatients", "ajax_stats_patient_state");
    if (form) {
      url.addFormData(form);
    }

    url.requestUpdate("patient_stats");

    return false;
  },

  downloadCSV: function () {
    var form = getForm("filter_graph_bar_patient_state");
    new Url("dPpatients", "ajax_export_stats_patient_state", "raw")
      .addFormData(form)
      .popup(200, 200)
  }
};