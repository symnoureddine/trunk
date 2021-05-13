/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

JournalEnvoiXml = window.JournalEnvoiXml || {
  open: function(journalId) {
    new Url('facturation', 'ajax_journal_envoi_xml_show')
      .addParam('journal_id', journalId)
      .requestModal();
  }
};
