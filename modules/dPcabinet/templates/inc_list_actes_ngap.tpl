{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{ if $subject->_ref_actes_ngap }}
<table class="tbl">
  <tr>
    <th class="category">Actes NGAP</th>
  </tr>
  {{foreach from=$subject->_ref_actes_ngap item=acte_ngap}}
  <tr>
    <td class="text">
      <span onmouseover="ObjectTooltip.createEx(this, '{{$acte_ngap->_guid}}');">{{$acte_ngap->_shortview}}:</span>
        {{$acte_ngap->_libelle}}
    </td>
  </tr>
  {{/foreach}}
</table>
{{/if}}