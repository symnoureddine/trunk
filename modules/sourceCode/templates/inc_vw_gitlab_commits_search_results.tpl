{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  changePage = function(start) {
    var form = getForm("search-gitlab-commits-form");
    $V(form.start, start);
    form.onsubmit();
  };

  Main.add(function () {
    $('search-gitlab-commits-results-container').fixedTableHeaders();
  });
</script>

{{assign var=display_checkboxes value=false}}
{{if $total > 0 && ($task_create || $task_link)}}
  {{assign var=display_checkboxes value=true}}
{{/if}}

{{mb_include module=system template=inc_pagination change_page='changePage' total=$total current=$start step=$limit}}

<div id="search-gitlab-commits-results-container">
  <table class="main tbl me-w100" id="search-gitlab-commits-results-container-table">
    <thead>
    <tr>
      {{if $display_checkboxes}}
        <th class="narrow">
          <input type="checkbox" name="allCommits" onclick="Tasking.Commit.selectAllCommits(this.checked);">
        </th>
      {{/if}}
      <th class="narrow">{{mb_label class=CGitlabCommit field=authored_date}}</th>
      <th class="narrow">{{mb_label class=CGitlabCommit field=id}}</th>
      <th class="narrow">{{mb_label class=CGitlabCommit field=type}}</th>
      <th>{{mb_label class=CGitlabCommit field=title}}</th>
      <th class="narrow">{{mb_label class=CGitlabCommit field=ox_user_id}}</th>
      <th class="narrow">{{tr}}CGitlabProject{{/tr}}</th>
      <th class="narrow">{{tr}}CGitlabBranch{{/tr}}</th>
      <th class="narrow">{{tr}}CTaskingTicket|pl{{/tr}}</th>
    </tr>
    </thead>
    <tbody>
    {{foreach from=$lines item=line}}
      <tr id="gitlab-commit-line-{{$line->_id}}">
        {{mb_include module=sourceCode template=inc_vw_gitlab_commit_line line=$line}}
      </tr>
      {{foreachelse}}
      <tr>
        <td colspan="{{if $display_checkboxes}}9{{else}}8{{/if}}" class="empty" style="text-align: center;">
          {{tr}}CGitlabCommit.none{{/tr}}
        </td>
      </tr>
    {{/foreach}}
    </tbody>
  </table>
</div>