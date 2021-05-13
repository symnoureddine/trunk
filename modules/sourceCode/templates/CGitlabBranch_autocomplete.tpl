{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<ul style="text-align: left">
  {{foreach from=$matches item=match}}
    <li>
      <span class="data_autocomplete"
            data-class="{{$match->_class}}"
            data-id="{{$match->_id}}"
            data-guid="{{$match->_guid}}">
        <strong>{{$match->_view}}</strong>
      </span>
      {{if $match->_ref_gitlab_project->ox_gitlab_project_id}}
        <small class="text compact" style="float: right;">
          {{$match->_ref_gitlab_project->name}}
        </small>
      {{/if}}
    </li>
    {{foreachelse}}
    <li class="empty">{{tr}}CGitlabBranch.none{{/tr}}</li>
  {{/foreach}}
</ul>