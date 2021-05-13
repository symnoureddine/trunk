{{*
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="edit-authentication-factor" method="post" onsubmit="return AuthenticationFactor.submitFactor(this);">
  {{mb_key object=$authentication_factor}}
  {{mb_class object=$authentication_factor}}
  <input type="hidden" name="del" value="" />

  <table class="main form">
    {{mb_include module=system template=inc_form_table_header object=$authentication_factor}}

    <col style="width: 20%;" />

    <tr>
      <th>{{mb_label object=$authentication_factor field=enabled}}</th>
      <td>
        {{if !$authentication_factor->_id || !$authentication_factor->isEnabled()}}
          {{mb_value object=$authentication_factor field=enabled}}
        {{else}}
          {{mb_field object=$authentication_factor field=enabled}}
        {{/if}}
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$authentication_factor field=priority}}</th>
      <td>{{mb_field object=$authentication_factor field=priority increment=true form='edit-authentication-factor'}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$authentication_factor field=type}}</th>
      <td>{{mb_field object=$authentication_factor field=type typeEnum='radio' onchange='AuthenticationFactor.toggleTypeFields(this);'}}</td>
    </tr>

    <tbody class="authentication-factor-type authentication-factor-type-email"
           {{if $authentication_factor->type != 'email'}}style="display: none;"{{/if}}>
    {{assign var=canNull value=true}}
    {{if $authentication_factor->type == 'email'}}
      {{assign var=canNull value=false}}
    {{/if}}

    <tr>
      <th>{{mb_label object=$authentication_factor field=_email}}</th>
      <td>
        {{if $authentication_factor->type == 'email'}}
          {{mb_field object=$authentication_factor field=_email}}
        {{else}}
          {{mb_field object=$authentication_factor field=_email canNull=true}}
        {{/if}}
      </td>
    </tr>
    </tbody>

    <tbody class="authentication-factor-type authentication-factor-type-sms"
           {{if $authentication_factor->type != 'sms'}}style="display: none;"{{/if}}>
    {{assign var=canNull value=true}}
    {{if $authentication_factor->type == 'sms'}}
      {{assign var=canNull value=false}}
    {{/if}}

    <tr>
      <th>{{mb_label object=$authentication_factor field=_phone_prefix}}</th>
      <td>
        {{if $authentication_factor->type == 'sms'}}
          {{mb_field object=$authentication_factor field=_phone_prefix size=3}}

          {{mb_label object=$authentication_factor field=_phone_number}}
          {{mb_field object=$authentication_factor field=_phone_number}}
        {{else}}
          {{mb_field object=$authentication_factor field=_phone_prefix size=3 canNull=true}}

          {{mb_label object=$authentication_factor field=_phone_number}}
          {{mb_field object=$authentication_factor field=_phone_number canNull=true}}
        {{/if}}
      </td>
    </tr>
    </tbody>

    <tr>
      <td class="button" colspan="2">
        <button type="submit" class="save">{{tr}}common-action-Save{{/tr}}</button>

        {{if $authentication_factor->_id}}
          {{if !$authentication_factor->isEnabled()}}
            <button type="button" class="tick" onclick="AuthenticationFactor.enableFactor('{{$authentication_factor->_id}}');">
              {{tr}}CAuthenticationFactor-action-Enable{{/tr}}
            </button>
          {{/if}}
          <button type="button" class="trash" onclick="AuthenticationFactor.confirmFactorDeletion(this.form);">
            {{tr}}common-action-Delete{{/tr}}
          </button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>