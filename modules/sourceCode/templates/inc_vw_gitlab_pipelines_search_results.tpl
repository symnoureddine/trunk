{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  changePage = function(start) {
    var form = getForm("search-gitlab-pipelines-form");
    $V(form.start, start);
    form.onsubmit();
  };

  Main.add(function () {
    $('search-gitlab-pipelines-results-container').fixedTableHeaders();
  });
</script>

{{mb_include module=system template=inc_pagination change_page='changePage' total=$total current=$start step=$limit}}

<div id="search-gitlab-pipelines-results-container">
  <table class="main tbl me-w100" id="search-gitlab-pipelines-results-container-table">
    <thead>
      <tr>
        <th colspan="7" class="me-border-right me-text-align-center">{{tr}}CGitlabPipeline{{/tr}}</th>
        <th colspan="10" class="me-text-align-center">{{tr}}CGitlabJob|pl{{/tr}}</th>
      </tr>
      <tr>
        <th class="narrow" style="text-align:center;">{{mb_label class=CGitlabPipeline field=status}}</th>
        <th class="narrow">{{mb_label class=CGitlabPipeline field=id}}</th>
        <th class="narrow">{{mb_label class=CGitlabPipeline field=ref}}</th>
        <th class="narrow">{{tr}}CGitlabProject{{/tr}}</th>
        <th class="narrow">{{mb_label class=CGitlabPipeline field=finished_at}}</th>
        <th class="narrow" style="text-align:center;">{{mb_label class=CGitlabPipeline field=duration}}</th>
        <th class="narrow me-border-right" style="text-align:center;">{{mb_label class=CGitlabPipeline field=coverage}}</th>
        {{* Report *}}
        {{mb_include module=sourceCode template=inc_vw_gitlab_pipelines_search_results_report}}
      </tr>
    </thead>
    <tbody>
    {{foreach from=$lines item=line}}
      <tr id="gitlab-pipeline-line-{{$line->_id}}" class="gitlab-pipeline-line">
        {{mb_include module=sourceCode template=inc_vw_gitlab_pipeline_line line=$line}}
      </tr>
      {{foreachelse}}
      <tr>
        <td colspan="17" class="empty" style="text-align: center;">
          {{tr}}CGitlabPipeline.none{{/tr}}
        </td>
      </tr>
    {{/foreach}}
    </tbody>
  </table>
</div>
