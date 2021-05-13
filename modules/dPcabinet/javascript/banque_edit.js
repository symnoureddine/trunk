/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

BanqueEdit = {
  edit : function(banqueId, buttonElement) {
    new Url('cabinet', 'vw_banques')
      .addParam('banque_id', banqueId)
      .addParam('edit_mode', 1)
      .requestUpdate('banque_edit_container');
    $$('.banque-line').invoke('removeClassName', 'selected');
    if (buttonElement) {
      buttonElement.up('tr').addClassName('selected');
    }
  },
  save : function(form) {
    return onSubmitFormAjax(form, function() {
      Control.Tabs.GroupedTabs.refresh();
    });
  },
  delete : function(form, options) {
    options.ajax = 1;
    return confirmDeletion(
      form,
      options,
      Control.Tabs.GroupedTabs.refresh
    );
  }
};
