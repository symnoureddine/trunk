/**
 * @package Mediboard\Messagerie
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

Messagerie = {
  account_url: null,

  openModal: function(account_guid) {
    var url = new Url('messagerie', 'ajax_view_messagerie');
    url.addParam('account_guid', account_guid);
    url.modal({width: 1200, height: 800});
  },

  manageAccounts: function() {
    Messagerie.account_url = new Url('messagerie', 'ajax_manage_accounts');
    Messagerie.account_url.requestModal(500);
  },

  refreshManageAccounts: function() {
    Messagerie.account_url.refreshModal();
  },

  addAccount: function() {
    var url = new Url('messagerie', 'ajax_add_account');
    url.requestModal(500, 600, {onClose: Messagerie.refreshManageAccounts.curry()});
  }
};