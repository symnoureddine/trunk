/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

MediusersCh = {
  loadArchivesFacturation: function(user_id) {
    var url = new Url('mediusers', 'ajax_edit_source_mediuser');
    url.addParam('user_id', user_id);
    url.requestUpdate('sources');
  },
  listingComptes: function(user_id) {
    var url = new Url('mediusers', 'vw_list_comptech_user');
    url.addParam('user_id', user_id);
    url.requestModal(800);
  },
  editCompte: function(compte_ch_id, user_id) {
    var url = new Url('mediusers', 'vw_edit_comptech_user');
    url.addParam('compte_ch_id', compte_ch_id);
    url.addParam('user_id', user_id);
    url.requestModal(null, 220);
  },
  onSaveCompteCh: function(form) {
    return onSubmitFormAjax(form, {
      onComplete: function() {
        Control.Modal.close();
        Control.Modal.refresh();
      }
    });
  }
};