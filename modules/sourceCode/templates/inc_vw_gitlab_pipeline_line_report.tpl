{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $report}}

  {{assign var=job value=$report->_ref_gitlab_job}}

  <td class="compact {{if $job->status === "success"}}ok{{elseif $job->status === "failed"}}error{{else}}empty{{/if}}" style="text-align:center;">
    {{tr}}CGitlabJob._statuses_list.{{$job->status}}{{/tr}}
  </td>
  <td style="text-align:center;">
    <b>{{mb_value object=$report field=classes_ratio}}%</b>
    <small style="display: block;">{{mb_value object=$report field=classes_covered}} / {{mb_value object=$report field=classes_all}}</small>
  </td>
  <td style="text-align:center;">
    <b>{{mb_value object=$report field=methods_ratio}}%</b>
    <small style="display: block;">{{mb_value object=$report field=methods_covered}} / {{mb_value object=$report field=methods_all}}</small>
  </td>
  <td style="text-align:center;">
    <b>{{mb_value object=$report field=lines_ratio}}%</b>
    <small style="display: block;">{{mb_value object=$report field=lines_covered}} / {{mb_value object=$report field=lines_all}}</small>
  </td>
  <td style="text-align:center;" class="{{if !$report->tests}}type_error{{/if}}">
    <b>{{mb_value object=$report field=tests}}</b>
  </td>
  <td style="text-align:center;" class="{{if !$report->assertions}}type_error{{/if}}">
    <b>{{mb_value object=$report field=assertions}}</b>
  </td>
  <td style="text-align:center;" class="{{if $report->failures}}type_error{{else}}empty{{/if}}">
    {{mb_value object=$report field=failures}}
  </td>
  <td style="text-align:center;" class="{{if $report->errors}}type_error{{else}}empty{{/if}}">
    {{mb_value object=$report field=errors}}
  </td>
  <td style="text-align:center;" class="{{if $report->skipped}}type_warning{{else}}empty{{/if}}">
    {{mb_value object=$report field=skipped}}
  </td>
  <td style="text-align:center;">
    <button type="button" class="zoom-in notext compact"
            onclick="Gitlab.showJobClassReports('{{$report->_id}}');">
      {{tr}}CGitlabJobClassReport-action-List{{/tr}}
    </button>
    {{if $report->_coverage_html_link}}
      <a class="button notext"
         target="_blank"
         href="{{$report->_coverage_html_link}}"
         title="{{tr}}CGitlabJobTestsReport-action-Open-coverage-html-report{{/tr}}">
        <i class="fas fa-folder-open"></i>
      </a>
    {{/if}}
  </td>
{{else}}
  <td colspan="11" class="empty" style="text-align:center;">
    {{tr}}CGitlabJobTestsReport.none{{/tr}}
  </td>
{{/if}}
