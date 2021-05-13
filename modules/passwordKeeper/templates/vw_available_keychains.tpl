{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=passwordKeeper script=keeper ajax=true}}

<script>
  toggleField = function(input) {
    var user_id = input.get('user_id');

    if (user_id != '{{$app->user_id}}') {
      $V(input.form.elements.public, 1);
      input.form.elements.public.setAttribute('readonly', 'readonly');
      input.form.elements.public.setAttribute('disabled', 'disabled');
    }
    else {
      input.form.elements.public.removeAttribute('readonly');
      input.form.elements.public.removeAttribute('disabled');
    }
  }
</script>

<h2>{{$object}}</h2>

<hr />

<form name="keychain-entry_set-context" method="post" onsubmit="return Keeper.checkEntry(this);">
  {{mb_key object=$entry}}
  {{mb_class object=$entry}}
  {{mb_field object=$entry field=object_id hidden=true}}
  {{mb_field object=$entry field=object_class hidden=true}}
  {{mb_field object=$entry field=_passphrase hidden=true}}

  <table class="main tbl">
    <tr>
      <th>
        {{tr}}CKeychain{{/tr}}
        <button type="button" class="new notext compact" onclick="Keeper.editKeychain(null, {onClose: function() { Keeper.urlSetContext.refreshModal(); } });">
          {{tr}}CKeychain-action-Create{{/tr}}
        </button>
      </th>

      {{foreach name=keychain_loop from=$keychains item=_keychain}}
        <th class="section">
          <label>
            <input type="radio" class="notNull" name="keychain_id" value="{{$_keychain->_id}}" data-user_id="{{$_keychain->user_id}}"
                   onchange="toggleField(this);"
                   {{if $smarty.foreach.keychain_loop.first}}checked{{/if}} />
            <span onmouseover="ObjectTooltip.createEx(this, '{{$_keychain->_guid}}');">
              {{$_keychain}}
            </span>
          </label>
        </th>
      {{/foreach}}
    </tr>
  </table>

  <hr />

  <table class="main form">
    <tr>
      <th>{{mb_label object=$entry field=label}}</th>
      <td>{{mb_field object=$entry field=label}}</td>

      <th>{{mb_label object=$entry field=public}}</th>
      <td>{{mb_field object=$entry field=public}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$entry field=username}}</th>
      <td>{{mb_field object=$entry field=username}}</td>

      <th>{{mb_label object=$entry field=password}}</th>
      <td>{{mb_field object=$entry field=password canNull=true}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$entry field=comment}}</th>
      <td colspan="3">{{mb_field object=$entry field=comment size=50}}</td>
    </tr>

    <tr>
      <td class="button" colspan="4">
        <button type="submit" class="save">{{tr}}Save{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>