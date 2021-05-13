{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    var form = getForm('search-gitlab-projects-form');
    form.onsubmit();
  });
</script>

<form name="search-gitlab-projects-form" method="get" onsubmit="return onSubmitFormAjax(this, null, 'search-gitlab-projects-results');">
  <input type="hidden" name="m" value="sourceCode" />
  <input type="hidden" name="a" value="ajax_search_gitlab_projects" />
  <input type="hidden" name="start" value="0" />
  <input type="hidden" name="limit" value="{{$limit}}" />
  <input type="hidden" name="_order" value="name" />
  <input type="hidden" name="_way" value="{{$way}}" />

  <table class="main form me-no-box-shadow" id="search-gitlab-projects-form-table">
    <tr>
      <th>{{mb_label class=CGitlabProject field=name}}</th>
      <td>
        {{mb_field class=CGitlabProject field=name size=50 canNull=true}}
      </td>
      <th>{{mb_label class=CGitlabProject field=name_with_namespace}}</th>
      <td>
        {{mb_field class=CGitlabProject field=name_with_namespace size=50 canNull=true}}
      </td>
      <th>{{mb_label class=CGitlabProject field=ready}}</th>
      <td>
        {{mb_field class=CGitlabProject field=ready typeEnum='radio' value='1' onchange="Gitlab.searchProjects(this.form);"}}
      </td>
    </tr>

    <tr>
      <td colspan="6" class="button">
        <button type="submit" class="search" onclick="Gitlab.searchProjects(this.form);">
          {{tr}}Search{{/tr}}
        </button>
      </td>
    </tr>
  </table>
</form>

<div id="search-gitlab-projects-results"></div>