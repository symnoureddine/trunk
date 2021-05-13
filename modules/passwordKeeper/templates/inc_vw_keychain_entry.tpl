{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=keychain value=$entry->_ref_keychain}}

<td>
  <button type="button" class="edit notext compact" onclick="Keeper.editEntry('{{$entry->_id}}', '{{$entry->keychain_id}}');">
    {{tr}}Edit{{/tr}}
  </button>
</td>

<td>{{mb_value object=$entry field=label}}</td>

<td>
  {{foreach from=$entry->_ref_hypertext_links item=_hypertext_link}}
    <a href="{{$_hypertext_link->link}}" target="_blank">
      {{$_hypertext_link->name}} <i class="fas fa-external-link-alt"></i>
    </a>
  {{/foreach}}
</td>

<td style="text-align: center;">
  <button type="button" class="lookup" onclick="Keeper.revealEntry('{{$entry->_id}}', '{{$entry->keychain_id}}');">
    {{tr}}CKeychainEntry-action-Reveal{{/tr}}
  </button>
</td>

<td>{{mb_value object=$entry field=username}}</td>

<td style="text-align: center;">
  {{if $entry->_ref_object}}
    {{mb_value object=$entry field=object_id tooltip=true}}
  {{/if}}
</td>

<td class="text compact">{{mb_value object=$entry field=comment}}</td>

<td style="text-align: center;">
  {{mb_include module=system template=inc_vw_bool_icon value=$entry->public size='lg'}}
</td>