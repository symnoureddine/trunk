{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=sourceCode script=gitlab ajax=true}}

<form name="edit-gitlab-project" method="post" onsubmit="return Gitlab.submitProject(this);">
  {{mb_key object=$project}}
  {{mb_class object=$project}}
  <input type="hidden" name="del" value="" />

  <table class="main form">
    {{mb_include module=oxERP template=inc_object_header object=$project colspan=4}}

    <tr>
      <th>{{mb_label object=$project field=name}}</th>
      <td>{{mb_field object=$project field=name readonly='readonly'}}</td>
      <th>{{mb_label object=$project field=name_with_namespace}}</th>
      <td>{{mb_field object=$project field=name_with_namespace readonly='readonly'}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$project field=ready}}</th>
      <td>{{mb_field object=$project field=ready typeEnum='radio'}}</td>
      <th>{{mb_label object=$project field=web_url}}</th>
      <td>{{mb_field object=$project field=web_url readonly='readonly' size=50}}</td>
    </tr>

    <tr>
      <td class="button" colspan="4">
        <button type="submit" class="save">{{tr}}Save{{/tr}}</button>
        {{if $project->_id}}
          <button type="submit" class="trash" onclick="$V(getForm('edit-gitlab-project').del, 1);">{{tr}}Delete{{/tr}}</button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>