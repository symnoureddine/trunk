{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=config value='Ox\Mediboard\System\CConfigurationModelManager::getConfigSpec'|static_call:$_feature}}
{{if $is_last}}
  {{assign var=_list value='|'|explode:$_prop.list}}
  <select class="{{$_prop.string}}" name="c[{{$_feature}}]" {{if $is_inherited}} disabled {{/if}}>
    {{foreach from=$_list item=_item}}
      <option value="{{$_item}}" {{if $_item == $value}} selected {{/if}}>
        {{if "localize"|array_key_exists:$config}}
          {{tr}}config-{{$_feature|replace:' ':'-'}}.{{$_item}}{{/tr}}
        {{else}}
          {{$_item}}
        {{/if}}
      </option>
    {{/foreach}}
  </select>
{{else}}
  {{if "localize"|array_key_exists:$config}}
    {{tr}}config-{{$_feature|replace:' ':'-'}}.{{$value}}{{/tr}}
  {{else}}
    {{$value}}
  {{/if}}
{{/if}}