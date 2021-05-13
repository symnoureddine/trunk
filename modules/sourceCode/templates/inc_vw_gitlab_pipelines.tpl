
{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=sourceCode script=gitlab ajax=1}}
{{mb_script module=oxERP script=ox.erp ajax=1}}

<script>
  Main.add(function () {
    var form = getForm('search-gitlab-pipelines-form');

    Calendar.regField(form.elements.from_date);
    Calendar.regField(form.elements.to_date);

    Gitlab.makeProjectAutocomplete(form, form.elements._project_autocomplete);

    form.onsubmit();
  });
</script>

<form name="search-gitlab-pipelines-form" method="get" onsubmit="return onSubmitFormAjax(this, null, 'search-gitlab-pipelines-results');">
  <input type="hidden" name="m" value="sourceCode" />
  <input type="hidden" name="a" value="ajax_search_gitlab_pipelines" />
  <input type="hidden" name="project_id" value="" />
  <input type="hidden" name="ready" value="1" />
  <input type="hidden" name="start" value="0" />
  <input type="hidden" name="limit" value="{{$limit}}" />
  <input type="hidden" name="_order" value="finished_at" />
  <input type="hidden" name="_way" value="{{$way}}" />

  <table class="main form me-no-box-shadow" id="search-gitlab-pipelines-form-table">
    <tr>
      <th>{{mb_label class=CGitlabPipeline field=id}}</th>
      <td>
        {{mb_field class=CGitlabPipeline field=id size=40 canNull=true}}
      </td>
      <th>{{mb_label class=CGitlabPipeline field=status}}</th>
      <td>
        {{mb_field class=CGitlabPipeline field=_statuses_list}}
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
      <th>{{mb_label class=CGitlabPipeline field=finished_at}}</th>
      <td colspan="5">
        <input type="hidden" name="from_date" class="date" value="{{$from_date}}" onchange="$V(form.elements.start, '0');" />
        &raquo;
        <input type="hidden" name="to_date" class="date" value="{{$to_date}}" onchange="$V(form.elements.start, '0');" />
      </td>
    </tr>

    <tr>
      <td colspan="6" class="button">
        <button type="submit" class="search" onclick="Gitlab.searchPipelines(this.form);">
          {{tr}}Search{{/tr}}
        </button>
      </td>
    </tr>
  </table>
</form>

<div id="search-gitlab-pipelines-results"></div>
