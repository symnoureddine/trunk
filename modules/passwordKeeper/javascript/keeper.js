/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

Keeper = window.Keeper || {
  urlSetContext: null,
  _params:       null,

  manageKeychains: function () {
    var url = new Url('passwordKeeper', 'vw_keychains');
    url.pop(800, 600);
  },

  editKeychain: function (keychain_id, callback) {
    var url = new Url('passwordKeeper', 'ajax_edit_keychain');
    url.addParam('keychain_id', keychain_id);

    callback = callback || {
      onClose: function () {
        Keeper.showKeychains();
      }
    };

    var options = {method: 'post', showReload: false, getParameters: {m: 'passwordKeeper', a: 'ajax_edit_keychain'}};
    options = Object.extend(callback, options);

    url.requestModal(null, null, options);
  },

  submitKeychain: function (form, callback) {
    var params = {form: form, callback: callback};
    Keeper.promptPassphrase('submitKeychain', params);
    return false;
  },

  submitKeychainAction: function (params, passphrase) {
    var form = params.form;
    var keychain_id = $V(form.elements.keychain_id);
    var callback = params.callback;

    callback = callback || {
      onComplete: function () {
        Control.Modal.close();
      }
    };

    if (keychain_id && passphrase) {
      Modal.confirm(
        $T('CKeychain-confirm-You are about to change the keychain passphrase. Are you sure?'),
        {
          onOK: function () {
            $V(form.elements._renew, '1');
            return onSubmitFormAjax(form, callback);
          }
        }
      );
    }
    else {
      $V(form.elements._renew, '0');
      return onSubmitFormAjax(form, callback);
    }

    return false;
  },

  promptPassphrase: function (action, params) {
    var url = new Url('passwordKeeper', 'ajax_get_passphrase');
    url.addParam('keychain_id', params.keychain_id);

    var options = {method: 'post', getParameters: {m: 'passwordKeeper', a: 'ajax_get_passphrase'}};

    url.requestJSON(
      function (_passphrase) {
        if (_passphrase) {
          Keeper.doAction(action, params, _passphrase);
        }
        else {
          // Parameters storing
          Keeper._params = params;

          var prompt = $('keychain_prompt');

          if (!prompt) {
            var input = DOM.input({
              type: 'password',
              name: 'keychain_passphrase'
            }).setStyle('width: 90%');

            var label = DOM.label(null, $T('CKeychain-prompt-Please, give passphrase:'), input);
            var span = DOM.span(null, label);

            var btn = DOM.button({
              type:      'submit',
              className: 'tick'
            }, $T('Submit'));

            var action_input = DOM.input({
              type:  'hidden',
              name:  '_action',
              value: action
            });

            var form = DOM.form({
              name:     'prompt-passphrase',
              method:   'post',
              onsubmit: 'Keeper.getPassphrase(this); return false;'
            }, action_input, span, btn);

            prompt = DOM.div({
              id: 'keychain_prompt'
            }, form).setStyle('text-align: center; display:none;');

            document.body.insert(prompt);
          }

          var modal_options = {title: $T('CKeychain-legend-Passphrase prompt'), showClose: true, width: 300};
          Modal.open(prompt, modal_options);
          prompt.down('input[type=password]').focus();
        }
      }, options);
  },

  getPassphrase: function (form) {
    if (form) {
      var action = $V(form.elements._action);
      var _passphrase = $V(form.elements.keychain_passphrase).trim();

      if (!_passphrase) {
        alert($T('common-error-Missing parameter: %s', $T('CKeychain-_passphrase-desc')));
      }
      else {
        Control.Modal.close();
        $('keychain_prompt').remove();

        var params = Keeper._params;
        Keeper._params = null;

        Keeper.doAction(action, params, _passphrase);
      }
    }

    return false;
  },

  doAction: function (action, params, passphrase) {
    switch (action) {
      case 'submitKeychain':
        Keeper.submitKeychainAction(params, passphrase);
        break;

      case 'showKeychain':
        Keeper.showKeychainAction(params, passphrase);
        break;

      case 'editEntry':
        Keeper.editEntryAction(params, passphrase);
        break;

      case 'revealEntry':
        Keeper.revealEntryAction(params, passphrase);
        break;

      case 'checkEntry':
        Keeper.checkEntryAction(params, passphrase);
        break;

      default:
    }
  },

  showKeychain: function (keychain_id, callback) {
    var params = {keychain_id: keychain_id, callback: callback};
    Keeper.promptPassphrase('showKeychain', params);
  },

  showKeychains: function (dom_id) {
    var url = new Url('passwordKeeper', 'ajax_show_keychains');
    url.requestUpdate(dom_id || 'all-keychains');
  },

  showKeychainAction: function (params, passphrase) {
    var url = new Url('passwordKeeper', 'vw_keychain_entries');
    url.addParam('_passphrase', passphrase);
    url.addParam('keychain_id', params.keychain_id);

    var callback = params.callback || {
      onClose: function () {
        Keeper.showKeychains();
      }
    };

    var options = {method: 'post', showReload: false, getParameters: {m: 'passwordKeeper', a: 'vw_keychain_entries'}};
    options = Object.extend(callback, options);

    url.requestModal('60%', '70%', options);
  },

  editEntry: function (entry_id, keychain_id, callback, object_guid) {
    var params = {entry_id: entry_id, keychain_id: keychain_id, callback: callback, object_guid: object_guid};
    Keeper.promptPassphrase('editEntry', params);
  },

  editEntryAction: function (params, passphrase) {
    var url = new Url('passwordKeeper', 'ajax_edit_entry');
    url.addParam('entry_id', params.entry_id);
    url.addParam('keychain_id', params.keychain_id);
    url.addParam('object_guid', params.object_guid);

    var callback = params.callback || {
      onClose: function () {
        Keeper.refreshEntry(params.entry_id);
      }
    };

    var options = {method: 'post', showReload: false, getParameters: {m: 'passwordKeeper', a: 'ajax_edit_entry'}};
    options = Object.extend(callback, options);

    url.requestModal(null, null, options);
  },

  refreshEntry: function (entry_id, dom_id) {
    var url = new Url('passwordKeeper', 'ajax_refresh_entry');
    url.addParam('entry_id', entry_id);

    dom_id = dom_id || '_keychain_entry_';
    var options = {method: 'post', getParameters: {m: 'passwordKeeper', a: 'ajax_refresh_entry'}};

    url.requestUpdate(dom_id + entry_id, options);
  },

  refreshEntries: function (form) {
    form = form || getForm('show_keychain_entries');
    form.onsubmit();
  },

  submitEntry: function (form, callback) {
    callback = callback || {
      onComplete: function () {
        Control.Modal.close();
      }
    };

    return onSubmitFormAjax(form, callback);
  },

  revealEntry: function (entry_id, keychain_id, dom_id) {
    var params = {entry_id: entry_id, keychain_id: keychain_id, dom_id: dom_id};
    Keeper.promptPassphrase('revealEntry', params);
  },

  revealEntryAction: function (params, passphrase) {
    var url = new Url('passwordKeeper', 'ajax_reveal_entry');
    url.addParam('entry_id', params.entry_id);
    url.addParam('_passphrase', passphrase);

    var dom_id = params.dom_id || 'reveal_entry';

    var options = {method: 'post', getParameters: {m: 'passwordKeeper', a: 'ajax_reveal_entry'}};

    url.requestUpdate(dom_id, options);
  },

  setContext: function (object_guid) {
    var url = new Url('passwordKeeper', 'ajax_set_context');
    url.addParam('object_guid', object_guid);

    Keeper.urlSetContext = url;

    url.requestModal(750, 400);
  },

  checkEntry: function (form, callback) {
    callback = callback || {
      onComplete: function () {
        Control.Modal.close();

      }
    };

    var params = {form: form, callback: callback};
    Keeper.promptPassphrase('checkEntry', params);

    return false;
  },

  checkEntryAction: function (params, passphrase) {
    var form = params.form;

    $V(form.elements._passphrase, passphrase);
    form.elements.public.removeAttribute('disabled');

    return onSubmitFormAjax(form, params.callback);
  },

  confirmKeychainDeletion: function (form, callback) {
    Modal.confirm(
      $T('CKeychain-confirm-Delete this object?'),
      {
        onOK: function () {
          $V(form.elements.del, '1');
          Keeper.submitKeychain(form, callback);
        }
      }
    );
  },

  confirmEntryDeletion: function (form, callback) {
    Modal.confirm(
      $T('CKeychainEntry-confirm-Delete this object?'),
      {
        onOK: function () {
          $V(form.elements.del, '1');
          Keeper.submitEntry(form, callback);
        }
      }
    );
  },

  checkChallenge: function (keychain_id, callback) {
    var url = new Url('passwordKeeper', 'ajax_check_challenge');
    url.addParam('keychain_id', keychain_id);

    callback = callback || {
      onClose: function () {
        Keeper.showKeychains();
      }
    };

    url.requestModal(null, null, callback);
  },

  filterContext: function (input, filter) {
    elements = $$(filter);

    elements.invoke('show');

    var terms = $V(input);
    if (!terms) {
      return;
    }

    elements.invoke('hide');

    terms = terms.split(",");
    elements.each(function (e) {
      terms.each(function (term) {
        if (e.getText().like(term)) {
          e.show();
        }
      });
    });
  },

  onFilterContext: function (input, filter) {
    if (input.value == '') {
      // Click on the clearing button
      Keeper.filterContext(input, filter);
    }
  }
};