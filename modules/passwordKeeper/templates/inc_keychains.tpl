{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=passwordKeeper script=keeper}}
{{mb_script module=system         script=object_selector}}

<table class="main tbl" style="width: 20%;">
  <tr>
    <th class="narrow">
      <button type="button" class="new notext compact" onclick="Keeper.editKeychain();">
        {{tr}}CKeychain-action-Create{{/tr}}
      </button>
    </th>

    <th>
      {{mb_title class=CKeychain field=name}}
    </th>

    <th class="narrow">
      {{mb_title class=CKeychain field=user_id}}
    </th>

    <th class="narrow" title="{{tr}}CKeychain-public-desc{{/tr}}">
      <i class="fa fa-eye fa-lg"></i>
    </th>

    <th class="narrow" title="{{tr}}CKeychainEntry|pl{{/tr}}">
      <i class="fa fa-key fa-lg"></i>
    </th>
  </tr>

  {{foreach from=$keychains item=_keychain}}
    <tr>
      <td>
        {{if $_keychain->canRead()}}
          <button type="button" class="lookup notext compact" onclick="Keeper.showKeychain('{{$_keychain->_id}}');">
            {{tr}}Ckeychain-action-Lookup{{/tr}}
          </button>
          {{mb_include module=system template=inc_vw_abonnement object=$_keychain callback='Keeper.showKeychains'}}
          <button type="button" class="fas fa-ticket-alt notext compact" onclick="Keeper.checkChallenge('{{$_keychain->_id}}');">
            {{tr}}CKeychainChallenge-action-Check{{/tr}}
          </button>
        {{/if}}

        {{if $_keychain->canEdit()}}
          <button type="button" class="edit notext compact" onclick="Keeper.editKeychain('{{$_keychain->_id}}');">
            {{tr}}Edit{{/tr}}
          </button>
        {{/if}}
      </td>

      <td>
        {{mb_value object=$_keychain field=name}}
      </td>

      <td style="text-align: center;">
        {{mb_value object=$_keychain field=user_id tooltip=true}}
      </td>

      <td style="text-align: center;">
        {{mb_include module=system template=inc_vw_bool_icon value=$_keychain->public size='lg'}}
      </td>

      <td style="text-align: center;">
        <span {{if !$_keychain->_ref_available_keychain_entries}}class="empty"{{/if}}>
          {{$_keychain->_ref_available_keychain_entries|@count}}
        </span>
      </td>
    </tr>
    {{foreachelse}}
    <tr>
      <td colspan="5" class="empty">
        {{tr}}CKeychain.none{{/tr}}
      </td>
    </tr>
  {{/foreach}}
</table>