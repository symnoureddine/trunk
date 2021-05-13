/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

ImportMedecins = window.ImportMedecins || {
  total_size: 0,

  nextImport: function() {
    var form = getForm("medecin-do-import");

    if ($V(form.elements.continue) == 0) {
      return;
    }

    form.onsubmit();
  },

  startImport: function() {
    var form = getForm("medecin-do-import");
    $V(form.elements.continue, 1);

    var btn_start = $('start-import-medecin');
    btn_start.disable();

    var btn_stop = $('stop-import-medecin');
    btn_stop.enable();

    form.onsubmit();
  },

  stopImport: function() {
    var form = getForm("medecin-do-import");
    $V(form.elements.continue, 0);

    var btn_start = $('start-import-medecin');
    btn_start.enable();

    var btn_stop = $('stop-import-medecin');
    btn_stop.disable();
  },

  resetImport: function() {
    Modal.confirm($T('CMedecinImport-msg-reset offset ?'), {
      onOK: function() {
        var form = getForm("medecin-do-import");
        $V(form.elements.last_id, 0);

        $('progress_import_medecins').value = 0;
        $('pct-import-rpps').innerHTML = 0;
      }
    });
  },

  displayConflicts: function() {
    var url = new Url('openData', 'ajax_display_conflicts');
    url.requestModal('70%', '70%');
  },

  displayAuditConflicts: function() {
    var url = new Url('openData', 'ajax_display_conflicts');
    url.addParam('audit', 1);
    url.requestModal('70%', '70%');
  },

  handleConflictResolution: function(id) {
    var form = getForm('handle-conflicts');

    var url = new Url('openData', 'do_handle_import_conflict', 'dosql');
    url.addParam('medecin_id', id);
    url.addFormData(form);
    url.requestUpdate('action-picker-'+id, {method: "post"});
  },

  handleAllConflict: function(medecins_ids) {
    var ids = medecins_ids.split('|');
    for (var i = 0; i < ids.length; i++) {
      ImportMedecins.handleConflictResolution(ids[i]);
    }
  },

  changePage: function(page, audit) {
    var form = getForm('search-medecin-conflict');
    var url = new Url('openData', 'ajax_search_medecin_conflict');
    url.addParam('start', page);
    url.addParam('audit', audit);
    url.addFormData(form);
    url.requestUpdate('result-search-medecin-conflict');
  },

  deleteConflicts: function(audit) {
    var url = new Url('openData', 'do_delete_conflicts', 'dosql');
    url.addParam('audit', audit);
    url.requestUpdate('systemMsg', {method: 'post'});
  },

  displayDoublons: function() {
    var url = new Url('openData', 'ajax_display_doublons');

    url.requestModal('70%', '70%');
  },

  updateStats: function(nb_news, nb_exists, nb_conflicts, nb_used, nb_unused, nb_rpps, time, nb_tel_error) {
    var url = new Url('openData', 'ajax_update_stats');
    url.addParam('nb_news', nb_news);
    url.addParam('nb_exists', nb_exists);
    url.addParam('nb_conflicts', nb_conflicts);
    url.addParam('nb_used', nb_used);
    url.addParam('nb_unused', nb_unused);
    url.addParam('nb_rpps', nb_rpps);
    url.addParam('time', time);
    url.addParam('nb_tel_error', nb_tel_error);
    url.requestUpdate('import-complete-logs');
  },

  updateDisplay: function (last_line, conflicts_audit, conflicts, conflicts_per_medecin_audit, conflicts_per_medecin) {
    $V(getForm('medecin-do-import').elements.last_id, last_line);
    $('progress_import_medecins').value = last_line;
    var pct = (last_line/this.total_size) * 100;
    $('pct-import-rpps').innerHTML = pct.toFixed(2);
    $('nb-conflicts-import-audit').innerHTML = conflicts_audit;
    $('nb-conflicts-import-medecin-audit').innerHTML = conflicts_per_medecin_audit;
    $('nb-conflicts-import').innerHTML = conflicts;
    $('nb-conflicts-import-medecin').innerHTML = conflicts_per_medecin;
  },

  resetDisplay: function () {
    $('nb-conflicts-import-audit').innerHTML = 0;
    $('nb-conflicts-import-medecin-audit').innerHTML = 0;
    $('nb-conflicts-import').innerHTML = 0;
    $('nb-conflicts-import-medecin').innerHTML = 0;
  },

  changePageDoublon: function(page) {
    var url = new Url('openData', 'ajax_display_doublons');
    url.addParam('start', page);
    url.requestUpdate('display-medecins-doublons');
  },

  checkAllValues: function(medecin_id, type) {
    $$('input.medecin-'+medecin_id).each(function (input) {
      if (input.value === type) {
        input.checked = true;
      }
    });
  }
};