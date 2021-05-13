{{*
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<ul style="text-align: left;">

{{foreach from=$categories item=_category}}
  <li {{if $yoplet}} onclick="$V(this.up('div').up('div').next('input'), '{{$_category->_id}}');" {{/if}}>
    <div class="view">{{$_category->nom}}</div>
    <div class="value" style="display: none;">{{$_category->_id}}</div>
  </li>
{{/foreach}}

</ul>