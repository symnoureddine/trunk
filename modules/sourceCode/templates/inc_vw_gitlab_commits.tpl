
{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=sourceCode script=gitlab ajax=1}}
{{mb_script module=oxERP script=ox.erp ajax=1}}

<script>
  Main.add(function () {
    var form = getForm('search-gitlab-commits-form');

    Calendar.regField(form.elements.from_date);
    Calendar.regField(form.elements.to_date);

    Gitlab.makeProjectAutocomplete(form, form.elements._project_autocomplete);
    Gitlab.makeBranchAutocomplete(form, form.elements._branch_autocomplete);

    OXERP.makeMOEAutocomplete(form, form.elements._user_autocomplete, form.elements.user_id, false, 'CMediusers');

    form.onsubmit();
  });
</script>

<form name="search-gitlab-commits-form" method="get" onsubmit="return onSubmitFormAjax(this, null, 'search-gitlab-commits-results');">
  <input type="hidden" name="m" value="sourceCode" />
  <input type="hidden" name="a" value="ajax_search_gitlab_commits" />
  <input type="hidden" name="project_id" value="" />
  <input type="hidden" name="branch_id" value="" />
  <input type="hidden" name="ready" value="1" />
  <input type="hidden" name="user_id" value="{{if $user}}{{$user->_id}}{{/if}}" />
  <input type="hidden" name="task_link" value="{{$task_link}}">
  <input type="hidden" name="task_create" value="{{$task_create}}">
  <input type="hidden" name="start" value="0" />
  <input type="hidden" name="limit" value="{{$limit}}" />
  <input type="hidden" name="_order" value="authored_date" />
  <input type="hidden" name="_way" value="{{$way}}" />

  <table class="main form me-no-box-shadow" id="search-gitlab-commits-form-table">
    <tr>
      <th>{{mb_label class=CGitlabCommit field=id}}</th>
      <td>
        {{mb_field class=CGitlabCommit field=id size=40 canNull=true}}
      </td>
      <th>{{mb_label class=CGitlabCommit field=type}}</th>
      <td>
        {{mb_field class=CGitlabCommit field=_types_list_multi}}
      </td>
      <th>{{tr}}CGitlabProject{{/tr}}</th>
      <td>
        <input type="text"
               name="_project_autocomplete"
               class="autocomplete"
               value=""
               onchange="Gitlab.handleBranchAutocomplete(this.form, false, false, true);"/>
        <button type="button"
                class="erase notext compact"
                onclick="Gitlab.handleBranchAutocomplete(this.form, false, true);">
        </button>
      </td>
    </tr>

    <tr>
      <th>{{mb_label class=CGitlabCommit field=authored_date}}</th>
      <td colspan="3">
        <input type="hidden" name="from_date" class="date" value="{{$from_date}}" onchange="$V(form.elements.start, '0');" />
        &raquo;
        <input type="hidden" name="to_date" class="date" value="{{$to_date}}" onchange="$V(form.elements.start, '0');" />
      </td>
      <th>{{tr}}CGitlabBranch{{/tr}}</th>
      <td>
        <input disabled type="text"
               name="_branch_autocomplete"
               class="autocomplete"
               value="" />
        <button type="button"
                class="erase notext compact"
                onclick="Gitlab.handleBranchAutocomplete(this.form, false, false, true);">
        </button>
      </td>
    </tr>

    <tr>
      <th>{{mb_label class=CGitlabCommit field=title}}</th>
      <td colspan="3">
        {{mb_field class=CGitlabCommit field=title canNull=true value=$title}}
        <label>
          <input type="checkbox" name="no_task" {{if $no_task}}checked{{/if}}/>
          {{tr}}CGitlabCommit-action-Search-commits-without-task{{/tr}}
        </label>
      </td>
      <th>{{mb_label class=CGitlabCommit field=ox_user_id}}</th>
      <td>
        <input type="text" name="_user_autocomplete" class="autocomplete"
               value="{{if $user}}{{$user}}{{/if}}" />
        {{mb_include module=oxERP template=inc_auto_user_button field_input='this.form.user_id'}}
        <label>
          <input type="checkbox" name="no_user"/>
          {{tr}}CGitlabCommit-action-Search-commits-without-user{{/tr}}
        </label>
      </td>
    </tr>

    <tr>
      <td colspan="6" class="button">
        <button type="submit" class="search" onclick="Gitlab.searchCommits(this.form);">
          {{tr}}Search{{/tr}}
        </button>
        {{if $task_link}}
          <button type="button" class="new singleclick" onclick="Tasking.Commit.addCommits('{{$tasking_ticket_id}}');">
            {{tr}}CTaskingTicketCommit-action-create{{/tr}}
          </button>

          <button type="button" class="new" onclick="Tasking.Commit.editSvnCommit(null, '{{$tasking_ticket_id}}');">
            {{tr}}CTaskingSvnCommit-action-Create{{/tr}}
          </button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>

<div id="search-gitlab-commits-results"></div>
