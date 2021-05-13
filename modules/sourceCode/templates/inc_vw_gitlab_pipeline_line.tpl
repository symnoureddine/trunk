{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{* Status *}}
<td class="compact {{if $line->status === "success"}}ok{{elseif $line->status === "failed"}}error{{else}}empty{{/if}}" style="text-align:center;">
  {{tr}}CGitlabPipeline._statuses_list.{{$line->status}}{{/tr}}
</td>

{{* Id *}}
<td class="text">
  <a href="{{mb_value object=$line field=web_url}}" target="_blank" title="{{tr}}CGitlabPipeline-action-View on Gitlab{{/tr}}">
    #{{mb_value object=$line field=id}}
  </a>
</td>

{{* Ref *}}
<td class="compact">
  {{mb_value object=$line field=ref}}
</td>

{{* Project *}}
<td class="compact{{if !$line->_ref_gitlab_project->ox_gitlab_project_id}} error{{/if}}">
  <a href="{{mb_value object=$line->_ref_gitlab_project field=web_url}}" target="_blank" title="{{tr}}CGitlabProject-action-View on Gitlab{{/tr}}">
    {{$line->_ref_gitlab_project->_shortview}}
  </a>
</td>

{{* Date *}}
<td class="text">
  {{mb_value object=$line field=finished_at}}
</td>

{{* Duration (Human-readable field) *}}
<td class="text" style="text-align:center;">
  {{mb_value object=$line field=_hr_duration}}
</td>

{{* Coverage (Global) *}}
<td class="text{{if !$line->coverage}} empty{{/if}}" style="text-align:center;">
  {{if $line->coverage > 0}}
    {{mb_include
        module=system
        template=inc_progress_bar
        percentage=$line->coverage
        theme="modern"
        precision=2
    }}
  {{else}}
    {{tr}}CGitlabPipeline.coverage.none{{/tr}}
  {{/if}}
</td>

{{* Report *}}
{{mb_include module=sourceCode template=inc_vw_gitlab_pipeline_line_report report=$line->_report}}
