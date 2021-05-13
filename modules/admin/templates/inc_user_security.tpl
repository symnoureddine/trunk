{{*
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if !$user || !$user->_id}}
  {{mb_return}}
{{/if}}

{{* Todo: To uncomment when OTP *}}
{{*<div class="small-warning">*}}
{{*  Cette fonctionnalité est encore en cours de développement et peut être amenée à changer à tout moment.*}}
{{*  NE PAS UTILISER EN PRODUCTION.*}}
{{*</div>*}}

{{*{{mb_script module=admin script=authentication.factor ajax=true}}*}}

{{*<table class="main tbl">*}}
{{*  <tr>*}}
{{*    <th class="title" colspan="5">{{tr}}CAuthenticationFactor|pl{{/tr}}</th>*}}
{{*  </tr>*}}

{{*  <tr>*}}
{{*    <th class="narrow">*}}
{{*      <button type="button" class="new notext compact" onclick="AuthenticationFactor.editFactor(null, {onClose: AuthenticationFactor.showFactors });">*}}
{{*        {{tr}}CAuthenticationFactor-action-Create{{/tr}}*}}
{{*      </button>*}}
{{*    </th>*}}

{{*    <th class="narrow">{{mb_label class=CAuthenticationFactor field=priority}}</th>*}}
{{*    <th class="narrow">{{mb_label class=CAuthenticationFactor field=enabled}}</th>*}}
{{*    <th>{{mb_label class=CAuthenticationFactor field=type}}</th>*}}
{{*    <th>{{mb_label class=CAuthenticationFactor field=value}}</th>*}}
{{*  </tr>*}}

{{*  {{foreach from=$user->loadRefAuthenticationFactors() item=_factor}}*}}
{{*    <tr>*}}
{{*      <td>*}}
{{*        <button type="button" class="edit notext compact" onclick="AuthenticationFactor.editFactor('{{$_factor->_id}}');">*}}
{{*          {{tr}}common-action-Edit{{/tr}}*}}
{{*        </button>*}}
{{*      </td>*}}

{{*      <td style="text-align: center;">*}}
{{*        <div class="rank">{{mb_value object=$_factor field=priority}}</div>*}}
{{*      </td>*}}

{{*      <td style="text-align: center;">{{mb_include module=system template=inc_vw_bool_icon value=$_factor->enabled}}</td>*}}
{{*      <td>{{mb_value object=$_factor field=type}}</td>*}}
{{*      <td>{{mb_value object=$_factor field=value}}</td>*}}
{{*    </tr>*}}
{{*    {{foreachelse}}*}}
{{*    <tr>*}}
{{*      <td class="empty" colspan="5">{{tr}}CAuthenticationFactor.none{{/tr}}</td>*}}
{{*    </tr>*}}
{{*  {{/foreach}}*}}
{{*</table>*}}

<div id="user-kerberos-security">
  {{mb_include module=admin template=inc_user_kerberos_security user=$user}}
</div>