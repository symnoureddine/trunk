{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  changeClassReportPage = function(start) {
    var form = getForm("search-gitlab-job-class-reports-form");
    $V(form.start, start);
    form.onsubmit();
  };

  sortClassReportBy = function(order_col, order_way) {
    var form = getForm("search-gitlab-job-class-reports-form");
    $V(form.start, '0');
    $V(form._order, order_col);
    $V(form._way, order_way);
    form.onsubmit();
  };

  Main.add(function () {
    $('search-gitlab-job-class-report-line-results-container').fixedTableHeaders();
  });
</script>

{{mb_include module=system template=inc_pagination change_page='changeClassReportPage' total=$total current=$start step=$limit}}

<div id="search-gitlab-job-class-report-line-results-container">
  <table class="main tbl me-w100" id="search-gitlab-job-class-report-line-results-container-table">
    <thead>
    <tr>
      <th>
        {{mb_colonne class=CGitlabJobClassReport field=namespace order_col=$_order order_way=$_way function=sortClassReportBy}}
      </th>
      <th class="narrow">
        {{mb_colonne class=CGitlabJobClassReport field=lines_covered order_col=$_order order_way=$_way function=sortClassReportBy}}
      </th>
      <th class="narrow">
        {{mb_colonne class=CGitlabJobClassReport field=lines_all order_col=$_order order_way=$_way function=sortClassReportBy}}
      </th>
      <th>
        {{mb_colonne class=CGitlabJobClassReport field=coverage order_col=$_order order_way=$_way function=sortClassReportBy}}
      </th>
    </tr>
    </thead>
    <tbody>
    {{foreach from=$lines item=line}}
      <tr id="gitlab-job-class-report-line-{{$line->_id}}" class="gitlab-job-class-report-line">
        {{mb_include module=sourceCode template=inc_vw_gitlab_job_class_report_line line=$line}}
      </tr>
      {{foreachelse}}
      <tr>
        <td colspan="4" class="empty" style="text-align: center;">
          {{tr}}CGitlabJobClassReport.none{{/tr}}
        </td>
      </tr>
    {{/foreach}}
    </tbody>
  </table>
</div>
