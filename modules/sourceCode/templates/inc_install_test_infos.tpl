{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $functionCount == 0}}
  <div class="small-info"><span>{{tr}}sourceCode-msg-Test files not found{{/tr}}</span></div>
    {{mb_return}}
{{/if}}

<br />
<div>
  <strong>{{tr}}common-Test|pl{{/tr}} ({{$functionCount}})</strong>
  <ul>
      {{foreach from=$testsInfos key=module item=_testModuleInfo}}
        <li><strong><a href="#{{$module}}">{{tr}}module-{{$module}}-court{{/tr}}</a></strong></li>
        <ul>
            {{foreach from=$_testModuleInfo key=className item=_testInfos}}
              <li><a href="#{{$className}}">{{$className|substr:0:-4}}</a></li>
            {{/foreach}}
        </ul>
      {{/foreach}}
  </ul>
</div>
<br />
<div>
  <hr>
    {{foreach from=$testsInfos key=module item=_testModuleInfo}}
      <h2 id="{{$module}}">{{tr}}module-{{$module}}-long{{/tr}}</h2>
        {{foreach from=$_testModuleInfo key=className item=_testInfos}}
          <h3 id="{{$className}}">{{$className|substr:0:-4}}</h3>
            {{if array_key_exists('tags',$_testInfos.comment) }}
                {{if array_key_exists('screen',$_testInfos.comment.tags) }}
                    {{if substr_count($_testInfos.comment.tags.screen, ",")}}
                      <span style="color: #666; margin-left: 40px;">{{tr}}common-Screen|pl{{/tr}} : </span>
                    {{else}}
                      <span style="color: #666; margin-left: 40px;">{{tr}}common-Screen{{/tr}} : </span>
                    {{/if}}
                  <p style="display: inline-block">{{$_testInfos.comment.tags.screen}}</p>
                {{/if}}
                {{if array_key_exists('config', $_testInfos.comment.tags)}}
                  <br />
                  <span style="color: #666; margin-left: 40px;">{{tr}}common-Configuration{{/tr}} : </span>
                  <p style="display: inline-block">{{$_testInfos.comment.tags.config}}</p>
                {{/if}}
            {{/if}}
            {{foreach from=$_testInfos.functions key=functionName item=_testFunctionInfos}}
              <h4>{{$functionName}}</h4>
              <div>
                <blockquote>{{$_testFunctionInfos.comment|smarty:nodefaults|markdown|purify}}</blockquote>
              </div>
              <blockquote>
                  {{foreach from=$_testFunctionInfos.tags name=tags key=tagName item=_tag}}
                    <span style="color: #666;">{{$tagName}}</span>
                    :
                      {{if $smarty.foreach.tags.last}}
                          {{$_tag}}
                      {{else}}
                          {{$_tag}},
                      {{/if}}
                  {{/foreach}}
              </blockquote>
            {{/foreach}}
        {{/foreach}}
      <hr>
    {{/foreach}}
</div>
<div style="position: fixed; right: 10px; bottom: 40px; width: 10em;"><a class="button up notext" href="#"></a></div>

