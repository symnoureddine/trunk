{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{foreach from=$validation_xml->_logs_erreur item=log key=nom}}
  <tr>
    <th class="category" colspan="10">
      <span onmouseover="ObjectTooltip.createEx(this, '{{$log[0]}}')">{{$nom}}</span>
    </th>
  </tr>
  <tr>
    {{foreach from=$log key=key_log item=champ}}
      {{if $key_log != "0"}}
        <td>{{tr}}CEditBill-{{$champ}}{{/tr}}</td>
      {{/if}}
    {{/foreach}}
  </tr>
{{/foreach}}