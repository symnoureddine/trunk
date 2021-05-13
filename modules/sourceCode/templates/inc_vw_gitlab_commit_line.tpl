{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $task_create || $task_link}}
  <td class="narrow">
    <input class="checkCommit" type="checkbox" data-id="{{$line->_id}}"/>
  </td>
{{/if}}

{{* Date *}}
<td class="compact">
  {{mb_value object=$line field=authored_date}}
</td>

{{* Id / sha *}}
<td class="text">
  <a href="{{mb_value object=$line field=web_url}}" target="_blank" title="{{tr}}CGitlabCommit-action-View on Gitlab{{/tr}}">
    {{mb_value object=$line field=short_id}}
  </a>
</td>

{{* Type *}}
<td class="compact {{if $line->type}}type_{{$line->type}}{{else}}warning{{/if}}" style="text-align:center;">
  {{mb_value object=$line field=type}}
</td>

{{* Title / Message *}}
<td class="text">
  {{mb_value object=$line field=title}}
</td>

{{* User *}}
<td class="compact {{if !$line->ox_user_id}}warning{{/if}}">
  {{if $line->ox_user_id}}
    {{mb_value object=$line field=ox_user_id tooltip=true}}
  {{else}}
    {{mb_value object=$line field=author_email}}
  {{/if}}
</td>

{{* Project *}}
<td class="compact{{if !$line->_ref_project->ox_gitlab_project_id}} error{{/if}}" style="text-align: center;">
  <a href="{{mb_value object=$line->_ref_project field=web_url}}" target="_blank" title="{{tr}}CGitlabProject-action-View on Gitlab{{/tr}}">
    {{$line->_ref_project->_shortview}}
  </a>
</td>

{{* Branches *}}
<td class="compact{{if !$line->_ref_branch->ox_gitlab_branch_id}} error{{/if}}" style="text-align: center;">
  <a href="{{mb_value object=$line->_ref_branch field=web_url}}" target="_blank" title="{{tr}}CGitlabBranch-action-View on Gitlab{{/tr}}">
    <span class="tag_task" style="background-color: #ffffff; cursor: pointer; border: solid 1px gray; float:none;">
      {{$line->_ref_branch->_code}}
    </span>
  </a>
</td>

{{* Tasks *}}
<td class="text{{if $line->_ref_tasks|@count <= 0}} warning{{/if}}" style="text-align: center;">
  {{if $line->_ref_tasks|@count > 0}}
    <button class="search"
            onclick="Modal.open('tooltip-commits-tasks-{{$line->_id}}', {width: 500, showClose: true, title: '[{{$line->short_id}}] {{$line->title|truncate:50|smarty:nodefaults|JSAttribute}}'});">
      {{$line->_ref_tasks|@count}}
    </button>
    <div id="tooltip-commits-tasks-{{$line->_id}}" style="display: none;">
      <table class="tbl main">
          <tr>
            <th>
              {{mb_label class=CTaskingTicket field=tasking_ticket_id}}
            </th>
            <th>
              {{tr}}CTaskingTicket{{/tr}}
            </th>
            <th>
              {{mb_label class=CTaskingTicket field=status}}
            </th>
            <th>
              {{mb_label class=CTaskingTicket field=estimate}}
            </th>
            <th>
              {{mb_label class=CTaskingTicket field=real}}
            </th>
          </tr>
          {{foreach from=$line->_ref_tasks item=task}}
            <tr>
              <th>
                {{mb_value object=$task field=tasking_ticket_id}}
              </th>
              <td>
                {{mb_value object=$task tooltip=true}}
              </td>
              <td>
                {{mb_value object=$task field=status}}
              </td>
              <td style="text-align: center;">
                {{mb_value object=$task field=estimate}}
              </td>
              <td style="text-align: center;">
                {{mb_value object=$task field=real}}
              </td>
            </tr>
          {{/foreach}}
      </table>
    </div>
  {{else}}
    {{$line->_ref_tasks|@count}}
  {{/if}}
</td>
