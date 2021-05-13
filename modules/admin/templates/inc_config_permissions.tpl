{{*
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<style>
  #config-strong-passwords.hide {
    display: none;
  }
</style>

<form name="editConfigPermissions" method="post" onsubmit="return onSubmitFormAjax(this);">
  {{mb_configure module=$m}}
  
  <table class="form">
    {{assign var="class" value="CUser"}}
    <tr>
      <th class="category" colspan="2">{{tr}}config-{{$m}}-{{$class}}{{/tr}}</th>
    </tr>

    {{mb_include module=system template=inc_config_str var=max_login_attempts}}
    {{mb_include module=system template=inc_config_num var=lock_expiration_time numeric=true size=2}}
    {{*{{mb_include module=system template=inc_config_str var=force_inactive_old_authentification numeric=true size=2}}*}}
    {{*{{mb_include module=system template=inc_config_str var=probability_force_inactive_old_authentification numeric=true size=2}}*}}

    <tr>
      <th class="category" colspan="2">{{tr}}common-Password|pl{{/tr}}</th>
    </tr>

    {{mb_include module=system template=inc_config_bool var=strong_password}}
    {{mb_include module=system template=inc_config_bool var=apply_all_users}}
    {{mb_include module=system template=inc_config_bool var=enable_admin_specific_strong_password}}
    {{mb_include module=system template=inc_config_bool var=allow_change_password}}
    {{mb_include module=system template=inc_config_bool var=force_changing_password}}
    {{mb_include module=system template=inc_config_enum var=password_life_duration values="15 day|1 month|2 month|3 month|6 month|1 year"}}
    {{mb_include module=system template=inc_config_enum var=reuse_password_probation_period values='none|1-week|2-week|3-week|1-month|2-month|3-month|6-month|1-year|never'}}
    {{mb_include module=system template=inc_config_num var=coming_password_expiration_threshold numeric=true size=2}}
    {{mb_include module=system template=inc_config_str var=custom_password_recommendations textarea=true rows=10}}

    <tbody id="config-strong-passwords">
    <tr>
      <th class="category" colspan="2">{{tr}}common-Strong password setting|pl{{/tr}}</th>
    </tr>

    <tr>
      <th></th>
      <td>
        <div class="small-info">
          La politique de mots de passe sécurisés ne s'applique que si la configuration "Forcer des mots de passe sécurisés" est active.
        </div>
      </td>
    </tr>

    {{mb_include module=system template=inc_config_str var=strong_password_min_length class='CPasswordSpec' numeric=true size=2}}
    {{mb_include module=system template=inc_config_bool var=strong_password_alpha_chars class='CPasswordSpec'}}
    {{mb_include module=system template=inc_config_bool var=strong_password_upper_chars class='CPasswordSpec'}}
    {{mb_include module=system template=inc_config_bool var=strong_password_num_chars class='CPasswordSpec'}}
    {{mb_include module=system template=inc_config_bool var=strong_password_special_chars class='CPasswordSpec'}}

    {{mb_include module=system template=inc_config_str var=admin_strong_password_min_length class='CPasswordSpec' numeric=true size=2}}
    {{mb_include module=system template=inc_config_bool var=admin_strong_password_alpha_chars class='CPasswordSpec'}}
    {{mb_include module=system template=inc_config_bool var=admin_strong_password_upper_chars class='CPasswordSpec'}}
    {{mb_include module=system template=inc_config_bool var=admin_strong_password_num_chars class='CPasswordSpec'}}
    {{mb_include module=system template=inc_config_bool var=admin_strong_password_special_chars class='CPasswordSpec'}}
    </tbody>

    <tr>
      <th class="category" colspan="2">{{tr}}common-Kerberos authentication{{/tr}}</th>
    </tr>

    {{mb_include module=system template=inc_config_bool var=enable_kerberos_authentication class='CKerberosLdapIdentifier'}}
    {{mb_include module=system template=inc_config_bool var=enable_login_button class='CKerberosLdapIdentifier'}}

    <tr>
      <th class="category" colspan="2">{{tr}}common-Strong authentication{{/tr}}</th>
    </tr>

    {{if !$authentication_source || !$authentication_source->_id}}
      <tr>
        <td colspan="2">
          <div class="small-error">
            {{tr}}CAuthenticationFactor-error-No SMTP source configured. By-email validation will not work.{{/tr}}
          </div>
        </td>
      </tr>
    {{/if}}

    {{mb_include module=system template=inc_config_str var=validation_period class='CAuthenticationFactor' numeric=true size=3}}
    {{mb_include module=system template=inc_config_enum var=force_strong_authentication class='CAuthenticationFactor' values='none|externals|all'}}

    <tr>
      <td class="button" colspan="2">
        <button class="modify">{{tr}}Save{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>
