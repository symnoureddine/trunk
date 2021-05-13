{{*
 * @package Mediboard\Import
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=campaigns value=[]}}
{{mb_default var=last_campaign_id value=null}}

<select name="import_campaign_id" id="import-campaign-select">
  <option value="">&mdash;</option>
  {{foreach from=$campaigns item=_campaign}}
    <option value="{{$_campaign->_id}}" {{if $last_campaign_id == $_campaign->_id}}selected{{/if}}>
      {{$_campaign->name}}
    </option>
  {{/foreach}}
</select>
