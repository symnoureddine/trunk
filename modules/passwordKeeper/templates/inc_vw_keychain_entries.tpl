{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    Control.Tabs.create('keychain-tabs', true);
  });
</script>

<table class="main layout">
  <tr>
    <td style="width: 20%;">
      <div style="text-align: center;">
        <label>
          <i class="fa fa-search fa-lg" title="{{tr}}common-action-Filter{{/tr}}"></i>
          <input type="search" onkeyup="Keeper.filterContext(this, '._keychain_entry');" onsearch="Keeper.onFilterContext(this, '._keychain_entry');" />
        </label>
      </div>

      <ul id="keychain-tabs" class="control_tabs_vertical small">
        {{foreach from=$entries_by_context key=_context item=_entries}}
          <li style="white-space: nowrap;">
            <a href="#keychain-{{$_context}}-tab">
              {{if $_context == 'none'}}
                {{$contextes.$_context}}
              {{else}}
                {{$contextes.$_context}} <strong class="compact">[{{tr}}{{'-'|explode:$_context|@first}}{{/tr}}]</strong>
              {{/if}}
            </a>
          </li>
        {{/foreach}}
      </ul>
    </td>

    <td>
      {{foreach from=$entries_by_context key=_context item=_entries}}
        <div id="keychain-{{$_context}}-tab" style="display: none;">
          <table class="main tbl">
            <tr>
              <th class="narrow">
                <button type="button" class="new notext compact"
                        onclick="Keeper.editEntry(null, '{{$keychain->_id}}', {onClose: function () { Keeper.refreshEntries(); }}, '{{$_context}}');">
                  {{tr}}CKeychainEntry-action-Create{{/tr}}
                </button>
              </th>

              <th>{{mb_title class=CKeychainEntry field=label}}</th>
              <th class="narrow"><i class="fas fa-external-link-alt"></i></th>
              <th>{{mb_title class=CKeychainEntry field=password}}</th>
              <th>{{mb_title class=CKeychainEntry field=username}}</th>
              <th>{{mb_title class=CKeychainEntry field=object_id}}</th>
              <th>{{mb_title class=CKeychainEntry field=comment}}</th>

              <th class="narrow" title="{{tr}}CKeychainEntry-public-desc{{/tr}}">
                <i class="fa fa-eye fa-lg"></i>
              </th>
            </tr>

            {{foreach from=$_entries item=_entry}}
              <tr class="_keychain_entry" id="_keychain_entry_{{$_entry->_id}}">
                {{mb_include module=passwordKeeper template=inc_vw_keychain_entry entry=$_entry}}
              </tr>
            {{foreachelse}}
              <tr>
                <td class="empty" colspan="8"></td>
              </tr>
            {{/foreach}}
          </table>
        </div>
      {{/foreach}}
    </td>
  </tr>
</table>


<div id="reveal_entry"></div>