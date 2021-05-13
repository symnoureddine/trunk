{{*
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  purgeUserAuthentication = function(user_id) {
    var url = new Url("admin", "do_user_authentication_purge", "dosql");
    url.addParam("user_id", user_id);
    url.requestUpdate(SystemMessage.id, {method: "post"});
  }
</script>

<h3 onmouseover="ObjectTooltip.createEx(this, '{{$user->_guid}}')">
  {{$user}}

  <span style="float: right;">
    {{$user->_count.authentications}} {{tr}}CUser-back-authentications{{/tr}}

    {{if $user->dont_log_connection}}
      <button class="trash" onclick="purgeUserAuthentication({{$user->_id}})">
        {{tr}}Purge{{/tr}}
      </button>
    {{/if}}
  </span>
</h3>

<table class="main tbl">
  <tr>
    <th>{{mb_title class=CUserAuthentication field=auth_method}}</th>
    <th>{{mb_title class=CUserAuthentication field=datetime_login}}</th>
    <th>{{mb_title class=CUserAuthentication field=expiration_datetime}}</th>
    <th>{{mb_title class=CUserAuthentication field=ip_address}}</th>
    <th>{{mb_title class=CUserAuthentication field=screen_width}}</th>
    <th>{{mb_title class=CUserAuthentication field=user_agent_id}}</th>
  </tr>

  {{foreach from=$list item=_auth}}
    <tr>
      <td>{{mb_value object=$_auth field=auth_method}}</td>
      <td>{{mb_value object=$_auth field=datetime_login}}</td>
      <td>
        {{if $_auth->expiration_datetime < $dtnow}}
          {{mb_value object=$_auth field=expiration_datetime}}
        {{/if}}
      </td>
      <td>{{mb_value object=$_auth field=ip_address}}</td>
      <td>
        {{if $_auth->screen_width && $_auth->screen_height}}
          {{mb_value object=$_auth field=screen_width}}x{{mb_value object=$_auth field=screen_height}}
        {{/if}}
      </td>
      <td class="compact">{{mb_value object=$_auth field=user_agent_id}}</td>
    </tr>
  {{foreachelse}}
    <tr>
      <td class="empty" colspan="6">{{tr}}CUserAuthentication.none{{/tr}}</td>
    </tr>
  {{/foreach}}
</table>