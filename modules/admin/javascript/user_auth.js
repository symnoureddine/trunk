/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

UserAuth = {
  makeUserAutocomplete: function (form, input_field) {
    var user_autocomplete = new Url("mediusers", "ajax_users_autocomplete");
    user_autocomplete.addParam('input_field', input_field.name);
    user_autocomplete.addParam("edit", 0);

    user_autocomplete.autoComplete(input_field, null, {
      minChars:           0,
      method:             "get",
      select:             "view",
      dropdown:           true,
      afterUpdateElement: function (field, selected) {
        if ($V(input_field) == "") {
          $V(input_field, selected.down('.view').innerHTML);
        }

        var id = selected.getAttribute("id").split("-")[2];
        $V(form.elements.user_id, id, true);
      }
    });
  },

  submitAuthFilter: function(form) {
    $V(form.type, 'success');
    Url.update(form, 'users-auth-results-success');
  
    $V(form.type, 'error');
    Url.update(form, 'users-auth-results-error');
  
    return false;
  },

  changePageUserAuth: function (type, start) {
    var form = getForm('search-users-auth');
    $V(form.elements.start, start);
    
    $V(form.type, type);
    Url.update(form, 'users-auth-results-'+type);
    
    $V(form.elements.start, 0);
  },

  destroySession: function (session_id) {
    var url = new Url('admin', 'do_destroy_session', 'dosql');
    url.addParam('session_id', session_id);
    url.requestUpdate('systemMsg', {method: 'post', onComplete: function () {Control.Modal.close(); getForm('search-users-auth').onsubmit();} });
  },

  edit: function(auth_id) {
    new Url('admin', 'vw_user_authentication')
      .addParam('auth_id', auth_id)
      .requestModal(600);
  },

  updateExpirationDateFilter: function(session_state_input) {
    var form = session_state_input.form;

    switch ($V(session_state_input)) {
      case 'all':
        Calendar.clear(form.elements._expiration_start_date);
        Calendar.clear(form.elements._expiration_end_date);
        break;

      case 'active':
        Calendar.setNow(form.elements._expiration_start_date);
        Calendar.clear(form.elements._expiration_end_date);
        break;

      case 'expired':
        Calendar.clear(form.elements._expiration_start_date);
        Calendar.setNow(form.elements._expiration_end_date);
        break;

      default:
    }
  }
};
