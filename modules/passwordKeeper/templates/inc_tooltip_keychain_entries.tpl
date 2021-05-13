{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{unique_id var=dom_id}}

<table class="main tbl" style="max-width: 500px;">
  <tr>
    <th>{{mb_title class=CKeychainEntry field=keychain_id}}</th>
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

  {{foreach from=$entries item=_entry}}
    <tr>
      <td>
        <strong>{{mb_value object=$_entry field=keychain_id tooltip=true}}</strong>
      </td>

      <td>{{mb_value object=$_entry field=label}}</td>

      <td>
        {{foreach from=$_entry->_ref_hypertext_links item=_hypertext_link}}
          <a href="{{$_hypertext_link->link}}" target="_blank">
            {{$_hypertext_link->name}} <i class="fas fa-external-link-alt"></i>
          </a>
        {{/foreach}}
      </td>

      <td style="text-align: center;">
        <button type="button" class="lookup" onclick="Keeper.revealEntry('{{$_entry->_id}}', '{{$_entry->keychain_id}}', 'reveal_{{$dom_id}}');">
          {{tr}}CKeychainEntry-action-Reveal{{/tr}}
        </button>
      </td>

      <td>{{mb_value object=$_entry field=username}}</td>

      <td style="text-align: center;">
        {{if $_entry->_ref_object}}
          {{mb_value object=$_entry field=object_id tooltip=true}}
        {{/if}}
      </td>

      <td class="text compact">{{mb_value object=$_entry field=comment}}</td>

      <td style="text-align: center;">
        {{mb_include module=system template=inc_vw_bool_icon value=$_entry->public size='lg'}}
      </td>
    </tr>
    {{foreachelse}}
    <tr>
      <td colspan="8" class="empty">
        {{tr}}CKeychainEntry.none{{/tr}}
      </td>
    </tr>
  {{/foreach}}
</table>

<div id="reveal_{{$dom_id}}"></div>