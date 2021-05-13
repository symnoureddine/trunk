{{*
 * @package Mediboard\Style\Mediboard
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_include style=mediboard_ext template=common ignore_errors=true}}
{{*<link href="http://fonts.googleapis.com/css?family=Roboto:300,400,500,700" rel="stylesheet">*}}
<link href="style/mediboard_ext/vendor/fonts/roboto/roboto.css" rel="stylesheet">

{{assign var=bg_custom value="images/pictures/bg_custom.jpg"}}

{{assign var=bg value=false}}
{{if is_file($bg_custom)}}
  {{assign var=bg value=true}}
{{/if}}

<script>
  Main.add(
    function() {
      getForm('loginFrm').username.focus();
    }
  );
</script>
<div id="login" {{if $bg}}class="me-bg-custom"{{/if}}></div>
<div class="login-form" id="login-form">
  {{if $conf.instance_role === "qualif"}}
    <div class="me-qualif-ribbon login-ribbon">
      {{mb_include style="mediboard_ext" template="logo_white" alt="Logo"}}
      <span class="me-ribbon-qualif-text">Qualif</span>
    </div>
  {{/if}}
  <form name="loginFrm" action="?" method="post" onsubmit="return checkForm(this)">
    <div>
      {{mb_include style="mediboard_ext" template="logo" id="mediboard-logo" alt="Logo Application"}}

      <div id="systemMsg">
        {{$errorMessage|nl2br|smarty:nodefaults}}
      </div>

      {{me_form_field label="common-User" field_class="me-margin-top-16 me-margin-bottom-16"}}
        <input type="text" class="notNull str" size="15" maxlength="20" name="username" />
      {{/me_form_field}}

      {{me_form_field label="CMbFieldSpec.type.password" field_class="me-margin-top-16 me-margin-bottom-16"}}
        <input type="password" class="notNull str" size="15" maxlength="32" name="password" />
      {{/me_form_field}}
      <button type="submit">
        {{tr}}Login{{/tr}}
      </button>

      {{if "mbHost"|module_active || $kerberos_button}}
        <div class="login-form-others">
          {{if $kerberos_button}}
            {{mb_include module=admin template=inc_kerberos_login_button}}
          {{/if}}

          {{if "mbHost"|module_active}}
            {{mb_include module=mbHost template=inc_login_cps}}
          {{/if}}
        </div>
      {{/if}}
    </div>
    <input type="hidden" name="login" value="{{$time}}" />
    <input type="hidden" name="redirect" value="{{$redirect}}" />
    <input type="hidden" name="dialog" value="{{$dialog}}" />
    <input type="text" name="_login" value="" style="position: absolute; top: -10000px;" tabindex="-1" />
    <input type="password" name="_pwd" value="" style="position: absolute; top: -10000px;" tabindex="-1" />
  </form>
</div>
{{if !$dialog}}
<div class="login-footer">
  <span class="me-text-align-left">{{$conf.company_name}}</span>
  <span class="me-text-align-center">{{$conf.product_name}}</span>
  <span class="me-text-align-right">
    {{if $applicationVersion.releaseCode && $applicationVersion.releaseTitle|capitalize}}
     {{$applicationVersion.releaseTitle|capitalize}}
    {{/if}}
  </span>
</div>
{{/if}}

{{mb_include style=mediboard_ext template=common_end nodebug=true}}
