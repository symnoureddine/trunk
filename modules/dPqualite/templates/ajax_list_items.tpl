{{*
 * @package Mediboard\Qualite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<option value=""> &mdash; Item</option>
{{foreach from=$items item=_item}}
  <option value="{{$_item->_id}}">
    {{$_item}}
  </option>
{{/foreach}}