{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<div class="small-info">
  Cette vue permet de créer les objets nécessaires et de configurer Mediboard pour réaliser les tests (fonctionnels & unitaires).
</div>

{{if !$objects[0]->user_id || !$objects[1]->user_id}}
  <form name="install-test" method="post" action="?" onsubmit="return onSubmitFormAjax(this, function(){location.reload()});">
    <input type="hidden" name="m" value="{{$m}}" />
    <input type="hidden" name="dosql" value="do_create_test_objects" />

    <table class="main form">
      <tr>
        <th class="title" colspan="4">{{tr}}common-title-Object creation|pl{{/tr}}</th>
      </tr>
      <tr>
          {{foreach from=$objects name=user_username item=_object}}
              {{assign var=i value=$smarty.foreach.user_username.index}}
              {{if !$_object->user_id}}
                <th>
                  <label for="install-test_user_username_{{$i}}" class="checkNull"
                         title="{{tr}}CMediusers-_user_last_name-desc{{/tr}}">{{tr}}CMediusers-_user_last_name-desc{{/tr}}</label>
                <td>
                  <input autocomplete="off" name="user_username_{{$i}}" value="{{$_object->_user_username}}"
                         class="str notNull minLength|3 reported styled-element" readonly="readonly" maxlength="255" type="text">
                </td>
              {{/if}}
          {{/foreach}}
      </tr>
      <tr>
          {{foreach from=$objects name=user_function item=_object}}
              {{if !$_object->user_id}}
                  {{assign var=i value=$smarty.foreach.user_function.index}}
                <th>
                  <label for="function_id_{{$i}}" class="notNull"
                         title="{{tr}}CMediusers-function_id-desc{{/tr}}">{{tr}}CMediusers-function_id{{/tr}}</label>
                </th>
                <td>
                  <select class="notNull" name="function_id_{{$i}}" style="width: 140px;">
                    <option value="">&mdash; {{tr}}Choose{{/tr}}</option>
                      {{foreach from=$functions item=_function}}
                        <option value="{{$_function->_id}}"
                                {{if $_object->function_id == $_function->_id}}selected{{/if}}>{{$_function}}</option>
                      {{/foreach}}
                  </select>
                </td>
              {{/if}}
          {{/foreach}}
      </tr>
      <tr>
          {{foreach from=$objects name=user_profiles item=_object}}
              {{if !$_object->user_id}}
                  {{assign var=i value=$smarty.foreach.user_profiles.index}}
                <th><label for="profile_id_{{$i}}" class="notNull"
                           title="{{tr}}CMediusers-_profile_id-desc{{/tr}}">{{tr}}CMediusers-_profile_id{{/tr}}</label></th>
                <td>
                  <select name="profile_id_{{$i}}" class="notNull" style="width: 140px;">
                    <option value="">&mdash; {{tr}}Choose{{/tr}}</option>
                      {{foreach from=$profiles item=_profile}}
                        <option value="{{$_profile->user_id}}">{{$_profile->user_username}}</option>
                      {{/foreach}}
                  </select>
                </td>
              {{/if}}
          {{/foreach}}
      </tr>
      <tr>
        <td class="button" colspan="4">
          <button class="submit" type="submit">{{tr}}Create{{/tr}}</button>
        </td>
      </tr>
    </table>
  </form>
{{/if}}

<form name="config-test" method="post" action="?" onsubmit="return onSubmitFormAjax(this, function(){location.reload()});">
    {{mb_configure module=$m}}
  
  <table class="main form">
    <tr>
      <th class="title" colspan="4">{{tr}}common-Configuration{{/tr}}</th>
    </tr>
    <tr>
      <td>{{mb_include module=system m="" template=inc_config_str var=base_url size=70}}</td>
    </tr>
    <tr>
      <td>{{mb_include module=system template=inc_config_str password=true var=phpunit_user_password size=35}}</td>
    </tr>
    <tr>
      <th colspan="2" class="category">{{tr}}config-sourceCode-selenium_browsers{{/tr}}</th>
    </tr>
      {{foreach from=$conf.$m.selenium_browsers item=value key=browserName}}
        <tr>
          <td>{{mb_include module=system template=inc_config_bool var=$browserName class=selenium_browsers}}</td>
        </tr>
      {{/foreach}}
    <tr>
      <td class="button" colspan="2">
        <button class="submit" type="submit">{{tr}}Save{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>

<table class="form">
  <tr>
    <th class="title" colspan="3">{{tr}}common-title-Object checking|pl{{/tr}}</th>
  </tr>
  <tr>
    <th class="category">{{tr}}common-Object{{/tr}}</th>
    <th class="category">{{tr}}common-Value{{/tr}}</th>
    <th class="category">{{tr}}common-Status{{/tr}}</th>
  </tr>
    {{foreach from=$objects item=_object}}
      <tr>
        <td>
            {{$_object->_class}}
        </td>
        <td>
            {{mb_value object=$_object}}
        </td>
          {{if $_object->user_id}}
            <td>{{mb_include module=system template=inc_vw_bool_icon value=1 size=2}}</td>
          {{else}}
            <td>{{mb_include module=system template=inc_vw_bool_icon value=0 size=2}}</td>
          {{/if}}
      </tr>
    {{/foreach}}
  <tr>
    <td>{{tr}}CConfiguration{{/tr}}</td>
    <td>{{tr}}config-base_url{{/tr}}</td>
      {{if $conf.base_url!=""}}
        <td>{{mb_include module=system template=inc_vw_bool_icon value=true size=2}}</td>
      {{else}}
        <td>{{mb_include module=system template=inc_vw_bool_icon value=false size=2}}</td>
      {{/if}}
  </tr>
  <tr>
    <td>{{tr}}CConfiguration{{/tr}}</td>
    <td>{{tr}}config-sourceCode-phpunit_user_password{{/tr}}</td>
      {{if $conf.$m.phpunit_user_password!=""}}
        <td>{{mb_include module=system template=inc_vw_bool_icon value=true size=2}}</td>
      {{else}}
        <td>{{mb_include module=system template=inc_vw_bool_icon value=false size=2}}</td>
      {{/if}}
  </tr>
  <tr>
    <td>{{tr}}CConfiguration{{/tr}}</td>
    <td>{{tr}}config-sourceCode-selenium_browsers{{/tr}}</td>
    <td>{{mb_include module=system template=inc_vw_bool_icon value=$isOneBrowserSet size=2}}</td>
  </tr>
</table>