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
        <strong>{{$match->name}}</strong>
      </span>
      <small class="text compact">
        {{$match->name_with_namespace}}
      </small>
    </li>
    {{foreachelse}}
    <li class="empty">{{tr}}CGitlabProject.none{{/tr}}</li>
  {{/foreach}}
</ul>