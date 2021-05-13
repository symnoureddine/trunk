/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

MaintenanceConfig = {

  /**
   * Modification des consentements
   */
  editConsentement() {
    new Url('patients', 'ajax_vw_consentement')
      .requestModal('30%', '30%');
  },

  /**
   * Affichage du nombre de patients qui seront impacté par le changement
   *
   * @param form
   */
  seeCountConsentement: function (form) {
    if (form.tag.value !== "" && form.allow_sms_notification.value !== "") {
      new Url('patients', 'ajax_count_consentement')
        .addParam("tag", form.tag.value)
        .addParam("consentement", form.allow_sms_notification.value)
        .requestUpdate('count_consentement', {
          onComplete: function () {
            MaintenanceConfig.clicButton($('submit'), false);
          }
        })
    } else {
      MaintenanceConfig.clicButton($('submit'), true);
    }
  },

  /**
   * Change le consentement des patients
   *
   * @param form
   */
  saveConsentement: function (form) {
    new Url('patients', 'ajax_edit_consentement')
      .addParam("tag", form.tag.value)
      .addParam("consentement", form.allow_sms_notification.value)
      .requestUpdate('systemMsg', {
        onComplete: function () {
          Control.Modal.close();
        }
      })
  },

  /**
   * Visibilité du bouton pour modifier les consentements
   *
   * @param button
   * @param value
   */
  clicButton: function (button, value) {
    $('submit').disabled = value;
  }
};
