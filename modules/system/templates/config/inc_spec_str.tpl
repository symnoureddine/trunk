{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $is_last}}
  <input type="text" class="{{$_prop.string}}" 
         name="c[{{$_feature}}]" size="50"
         value="{{$value|smarty:nodefaults|purify|JSAttribute|stripslashes}}" {{* needed to handle ", ', \ etc well *}}
    {{if $is_inherited}} disabled {{/if}} />
{{else}}
  {{$value|smarty:nodefaults|purify}}
{{/if}}