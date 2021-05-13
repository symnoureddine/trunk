
{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=sourceCode script=gitlab ajax=1}}
{{mb_script module=oxERP script=ox.erp ajax=1}}

<script>
  Main.add(function () {
    var form = getForm('search-gitlab-job-class-reports-form');

    form.onsubmit();
  });
</script>

<form name="search-gitlab-job-class-reports-form" method="get" onsubmit="return onSubmitFormAjax(this, null, 'search-gitlab-job-class-reports-results');">
  <input type="hidden" name="m" value="sourceCode" />
  <input type="hidden" name="a" value="ajax_search_gitlab_job_class_reports" />
  <input type="hidden" name="start" value="0" />
  <input type="hidden" name="limit" value="{{$limit}}" />
  <input type="hidden" name="_order" value="{{$order}}" />
  <input type="hidden" name="_way" value="{{$way}}" />
  <input type="hidden" name="tests_report_id" value="{{$tests_report_id}}" />

  <table class="main form me-no-box-shadow" id="search-gitlab-pipelines-form-table">

    <tr>
      <th>{{mb_label class=CGitlabJobClassReport field=namespace}}</th>
      <td>
        {{mb_field object=$class_report field=namespace canNull=true}}
      </td>
      <th>{{mb_label class=CGitlabJobClassReport field=coverage}}</th>
      <td>
        {{mb_field object=$class_report field=_coverage_from}}
        &raquo;
        {{mb_field object=$class_report field=_coverage_to}}
      </td>
    </tr>

    <tr>
      <td colspan="4" class="button">
        <button type="submit" class="search" onclick="Gitlab.searchJobClassReports(this.form);">
          {{tr}}Search{{/tr}}
        </button>
      </td>
    </tr>
  </table>
</form>

<div id="search-gitlab-job-class-reports-results"></div>
