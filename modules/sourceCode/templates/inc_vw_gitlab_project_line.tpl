{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<th>
  <button type="button" class="edit notext compact"
          onclick="Gitlab.editProject('{{$line->_id}}');">
    {{tr}}CGitlabProject-action-Edit{{/tr}}
  </button>
</th>

{{* Name *}}
<td class="text">
  {{if $line->web_url}}
    <a href="{{mb_value object=$line field=web_url}}" target="_blank" title="{{tr}}CGitlabProject-action-View on Gitlab{{/tr}}">
      {{mb_value object=$line field=name}}
    </a>
  {{else}}
    {{mb_value object=$line field=name}}
  {{/if}}
</td>

{{* Name with namespace *}}
<td class="text compact">
  {{mb_value object=$line field=name_with_namespace}}
</td>

{{* Branches *}}
<td class="text" style="text-align: center;">
  {{$line->_ref_branches|@count}}
</td>

{{* Ready *}}
<td class="text {{if $line->ready}}ok{{else}}error{{/if}}" style="text-align: center;">
  {{mb_value object=$line field=ready}}
</td>