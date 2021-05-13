{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="edit-keychain" method="post" onsubmit="return Keeper.submitKeychain(this);">
  {{mb_key object=$keychain}}
  {{mb_class object=$keychain}}
  <input type="hidden" name="del" value="" />
  <input type="hidden" name="_renew" value="" />
  {{mb_field object=$keychain field=user_id hidden=true}}

  <table class="main form">
    {{mb_include module=system template=inc_form_table_header object=$keychain colspan=4}}

    <tr>
      <th>{{mb_label object=$keychain field=name}}</th>
      <td>{{mb_field object=$keychain field=name}}</td>

      <th>{{mb_label object=$keychain field=public}}</th>
      <td>{{mb_field object=$keychain field=public}}</td>
    </tr>

    {{if !$keychain->_id}}
      <tr>
        <th>{{mb_label object=$keychain field=_passphrase}}</th>
        <td colspan="3">{{mb_field object=$keychain field=_passphrase size=50}}</td>
      </tr>
    {{/if}}

    <tr>
      <td class="button" colspan="4">
        <button type="submit" class="save">{{tr}}Save{{/tr}}</button>

        {{if $keychain->_id && $keychain->canEdit()}}
          <button type="button" class="trash" onclick="Keeper.confirmKeychainDeletion(this.form);">
            {{tr}}common-action-Delete{{/tr}}
          </button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>