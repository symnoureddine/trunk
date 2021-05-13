/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

Programme = {
  editProgramme: function (programme_id) {
    var url = new Url("patients", "ajax_edit_programme");
    url.addParam("programme_id", programme_id);
    url.requestModal("30%", null, {onClose: Control.Modal.refresh});
  },

  showPatientProgramme: function (programme_id) {
    var url = new Url("patients", "ajax_patient_programme");
    url.addParam("programme_id", programme_id);
    url.requestModal("30%", null);
  },

  /**
   * Select the year to filter the protocol inclusions based on it's beginning date
   *
   * @param {HTMLElement} element
   */
  selectYear: function (element) {
    new Url('patients', 'vw_programmes')
      .addParam('year', element.value)
      .requestUpdate('programmes');
  }
};

RegleEvt = {
  editRegle:      function (regle_id) {
    var url = new Url("patients", "ajax_edit_regle_alert_evt");
    url.addParam("regle_id", regle_id);
    url.requestModal("50%", null, {onClose: Control.Modal.refresh});
  },
  createSpanCIM:  function (value) {
    if (!value) {
      return;
    }
    var span = DOM.span({'className': 'span_name'}, value);
    var _line = DOM.span({'className': 'tag_tab'}, span);
    var del = DOM.span({
      'style':     'margin-right:5px;float:left;',
      'className': 'fas fa-trash-alt',
      'title':     'Supprimer',
      'onclick':   'this.up().remove();'
    });
    _line.insert(del);
    $('codes_cim_regle_alerte').insert(_line);
    $V(getForm('edit_program').keywords_code, '');
  },
  compactCodeCIm: function () {
    var form = getForm('edit_program');
    var codes_cim = [];
    form.select('span[class=span_name]').each(function (elt) {
      codes_cim.push(elt.innerHTML);
    });
    $V(form.diagnostics, codes_cim.join("|"));
    form.onsubmit();
  }
};