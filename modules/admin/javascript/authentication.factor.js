/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

AuthenticationFactor = window.AuthenticationFactor || {
    urlEdit: {},

    editFactor: function (factor_id, callback) {
      var url = new Url('admin', 'ajax_edit_authentication_factor');
      url.addParam('factor_id', factor_id);

      if (callback !== false) {
        callback = callback || {onClose: AuthenticationFactor.showFactors};
      }

      AuthenticationFactor.urlEdit = url;

      url.requestModal(800, 400, callback);
    },

    submitFactor: function (form, callback) {
      if (callback !== false) {
        callback = callback || {onComplete: $V(form.elements.authentication_factor_id) ? Control.Modal.refresh : Control.Modal.close};
      }

      return onSubmitFormAjax(form, callback);
    },

    confirmFactorDeletion: function (form) {
      Modal.confirm(
        $T('CAuthenticationFactor-confirm-Delete this object?'),
        {
          onOK: function () {
            $V(form.elements.del, '1');
            AuthenticationFactor.submitFactor(form, {onComplete: Control.Modal.close});
          }
        }
      );
    },

    enableFactor: function (factor_id) {
      var url = new Url('admin', 'vw_validate_authentication_factor');
      url.addParam('factor_id', factor_id);

      url.addParam('send', '1');
      url.addParam('callback', 'enable');

      url.requestModal(800, null,
        {
          method:        'post',
          getParameters: {
            m:         'admin',
            a:         'vw_validate_authentication_factor',
            factor_id: factor_id,
            callback:  'enable'
          }
        });
    },

    toggleTypeFields: function (input) {
      var type = $V(input);

      $$('.authentication-factor-type').invoke('hide');
      $$('.authentication-factor-type label').invoke('removeClassName', 'notNull');
      $$('.authentication-factor-type input').invoke('removeClassName', 'notNull');

      $$('.authentication-factor-type-' + type).invoke('show');
      $$('.authentication-factor-type-' + type + ' label').invoke('addClassName', 'notNull');
      $$('.authentication-factor-type-' + type + ' input').invoke('addClassName', 'notNull');
    },

    reloadPage: function () {
      window.location.reload();
      window.location.href = '?';
    },

    showFactors: function () {
      var url = new Url('admin', 'ajax_show_authentication_factors');
      url.requestUpdate('user-security');
    },

    resendValidationCode: function (factor_id) {
      var url = new Url('admin', 'do_send_validation_code', 'dosql');
      url.addParam('factor_id', factor_id);

      url.requestUpdate('systemMsg', {onComplete: AuthenticationFactor.reloadPage, method: 'post'});
    },

    sendNextFactor: function (factor_id) {
      var url = new Url('admin', 'do_send_next_factor', 'dosql');
      url.addParam('factor_id', factor_id);

      url.requestUpdate('systemMsg', {onComplete: AuthenticationFactor.reloadPage, method: 'post'});
    }
  };