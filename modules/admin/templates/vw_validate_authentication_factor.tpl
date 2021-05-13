{{*
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=admin script=authentication.factor ajax=true}}

{{mb_default var=callback value='login'}}

{{if !$authentication_factor->validation_code}}
  <div class="small-error">
    {{tr}}CAuthenticationFactor-error-No code to validate{{/tr}}
  </div>

  {{mb_return}}
{{/if}}

<div class="small-info">
  {{$authentication_factor->getSentValidationCodeMessage()}}
</div>

{{if !$authentication_factor->isEnabling()}}
  <div class="small-info">
    {{tr var1=$authentication_factor->_remaining_attempts}}CAuthenticationFactor-msg-Remaining attempt|pl: %d.{{/tr}}
  </div>
{{/if}}

<form name="validate-authentication-factor" method="post" onsubmit="return onSubmitFormAjax(this);">
  <input type="hidden" name="m" value="admin" />
  <input type="hidden" name="dosql" value="do_validate_authentication_factor" />
  <input type="hidden" name="callback" value="{{$callback}}" />
  <input type="hidden" name="factor_id" value="{{$authentication_factor->_id}}" />

  <table class="main form">
    <tr>
      <th>{{mb_label object=$authentication_factor field=validation_code}}</th>
      <td>{{mb_field object=$authentication_factor field=_validation_code class='notNull' style='text-transform: uppercase;'}}</td>
    </tr>

    <tr>
      <td colspan="2" class="button">
        <button type="submit" class="tick">
          {{tr}}common-action-Validate{{/tr}}
        </button>

        {{if !$authentication_factor->isEnabling()}}
          {{if $authentication_factor->checkAttempts()}}
            <button type="button" class="send" onclick="AuthenticationFactor.resendValidationCode('{{$authentication_factor->_id}}');">
              {{tr}}CAuthenticationFactor-action-Resend validation code{{/tr}}
            </button>
          {{/if}}

          {{foreach from=$next_authentication_factors item=_authentication_factor}}
            <button type="button" class="right" onclick="AuthenticationFactor.sendNextFactor('{{$_authentication_factor->_id}}');">
              {{mb_value object=$_authentication_factor field=type}}
            </button>
          {{/foreach}}
        {{/if}}
      </td>
    </tr>
  </table>
</form>