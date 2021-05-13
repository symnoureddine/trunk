{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=sourceCode script=gitlab ajax=1}}
{{mb_script module=oxERP script=ox.erp ajax=1}}

<script>
  Main.add(function () {
    var form = getForm('gitlab-generate-report');
    Calendar.regField(form.elements.from_date);
    Calendar.regField(form.elements.to_date);
    Gitlab.makeProjectAutocomplete(form, form.elements._project_autocomplete);
    Gitlab.makeBranchAutocomplete(form, form.elements._branch_autocomplete);
  });
</script>

<form name="gitlab-generate-report" method="get" onsubmit="return onSubmitFormAjax(this, null, 'gitlab-generate-report-results');">
  <input type="hidden" name="m" value="sourceCode" />
  <input type="hidden" name="a" value="generate_gitlab_report" />
  <input type="hidden" name="project_id" value="" />
  <input type="hidden" name="branch_id" value="" />
  <input type="hidden" name="ready" value="1" />

  <table class="main form me-no-box-shadow" id="gitlab-generate-report-form-table">
    <tr>
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
      <th>{{tr}}CGitlabBranch{{/tr}}</th>
      <td colspan="3">
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
      <th>{{tr}}common-Date{{/tr}}</th>
      <td>
        <input type="hidden" name="from_date" class="dateTime" value="{{$from_date}}" />
        &raquo;
        <input type="hidden" name="to_date" class="dateTime" value="{{$to_date}}" />
      </td>
      <th>{{tr}}Send-email{{/tr}}</th>
      <td>
        <input type="text" name="email" value="" class="str confidential styled-element" size="25" maxlength="255" id="gitlab-report-email">
      </td>
      <th>{{tr}}common-Logging{{/tr}}</th>
      <td>
        <label>
          <input type="checkbox" name="debug"/>
        </label>
      </td>
    </tr>
    <tr>
      <td colspan="6" class="button">
        <button type="submit" class="search">
          {{tr}}common-action-Generate{{/tr}}
        </button>
      </td>
    </tr>
  </table>
</form>

<div id="gitlab-generate-report-results"></div>
