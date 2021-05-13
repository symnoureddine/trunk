{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{unique_id var=form}}

<script>
  Main.add(function () {
    var form = getForm('{{$form}}');
    form.elements._passphrase.focus();
  });
</script>

<h2>
  <img src="modules/passwordKeeper/images/icon.png" width="16" height="16" />

  <span onmouseover="ObjectTooltip.createEx(this, '{{$keychain->_guid}}');">
    {{$keychain}}
  </span>
</h2>

<form name="{{$form}}" method="post" onsubmit="return onSubmitFormAjax(this);">
  <input type="hidden" name="m" value="passwordKeeper" />
  <input type="hidden" name="dosql" value="do_update_challenge" />
  {{mb_key object=$keychain}}

  <table class="main form">
    <tr>
      <th>{{mb_label object=$keychain->_ref_user_challenge field=last_modification_date}}</th>
      <td colspan="2">{{mb_value object=$keychain->_ref_user_challenge field=last_modification_date}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$keychain->_ref_user_challenge field=last_success_date}}</th>
      <td colspan="2">{{mb_value object=$keychain->_ref_user_challenge field=last_success_date}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$keychain field=_passphrase}}</th>

      <td>{{mb_field object=$keychain field=_passphrase size=35}}</td>

      <td><button type="submit" class="save">{{tr}}common-action-Save{{/tr}}</button></td>
    </tr>
  </table>
</form>