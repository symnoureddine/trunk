{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  changePage = function(start) {
    var form = getForm("search-gitlab-projects-form");
    $V(form.start, start);
    form.onsubmit();
  };

  Main.add(function () {
    $('search-gitlab-projects-results-container').fixedTableHeaders();
  });
</script>

{{mb_include module=system template=inc_pagination change_page='changePage' total=$total current=$start step=$limit}}

<div id="search-gitlab-projects-results-container">
  <table class="main tbl me-w100" id="search-gitlab-projects-results-container-table">
    <thead>
    <tr>
      <th class="narrow"></th>
      <th>{{mb_label class=CGitlabProject field=name}}</th>
      <th>{{mb_label class=CGitlabProject field=name_with_namespace}}</th>
      <th class="narrow">{{tr}}CGitlabBranch|pl{{/tr}}</th>
      <th class="narrow">{{mb_label class=CGitlabProject field=ready}}</th>
    </tr>
    </thead>
    <tbody>
    {{foreach from=$lines item=line}}
      <tr id="gitlab-project-line-{{$line->_id}}" {{if !$line->ready}}class="me-hatching"{{/if}}>
        {{mb_include module=sourceCode template=inc_vw_gitlab_project_line line=$line}}
      </tr>
      {{foreachelse}}
      <tr>
        <td colspan="4" class="empty" style="text-align: center;">
          {{tr}}CGitlabProject.none{{/tr}}
        </td>
      </tr>
    {{/foreach}}
    </tbody>
  </table>
</div>