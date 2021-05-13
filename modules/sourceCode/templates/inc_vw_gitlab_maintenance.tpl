{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=sourceCode script=gitlab ajax=1}}
{{mb_script module=oxERP script=ox.erp ajax=1}}

<script>
  Main.add(function () {
    var form = getForm('import-gitlab-commits-form');

    Calendar.regField(form.elements.from_date);
    Calendar.regField(form.elements.to_date);

    Gitlab.makeProjectAutocomplete(form, form.elements._project_autocomplete);
    Gitlab.makeBranchAutocomplete(form, form.elements._branch_autocomplete);
  });
</script>

<div style="width: 50%; float: left;" class="me-margin-left-4">

  {{* Projects and branches *}}
  <table id="import-gitlab-projects-branches">
    <tr>
      <td>
        <button onclick="Gitlab.importProjects();">
          {{tr}}Import{{/tr}} {{tr}}CGitlabProject|pl{{/tr}}
        </button>
      </td>
      <td>
        <button onclick="Gitlab.importProjectsBranches();">
          {{tr}}Import{{/tr}} {{tr}}CGitlabBranch|pl{{/tr}}
        </button>
      </td>
      <td>
        <button onclick="Gitlab.importProjectsPipelines();">
          {{tr}}Import{{/tr}} {{tr}}CGitlabPipeline|pl{{/tr}}
        </button>
      </td>
    </tr>
  </table>

  {{* Commits form *}}
  <form name="import-gitlab-commits-form" method="get" onsubmit="return onSubmitFormAjax(this, null, 'import-gitlab-commits-results');">
    <input type="hidden" name="m" value="sourceCode" />
    <input type="hidden" name="a" value="ajax_import_gitlab_commits" />
    <input type="hidden" name="project_id" value="" />
    <input type="hidden" name="branch_id" value="" />
    <input type="hidden" name="ready" value="0" />
    <input type="hidden" name="continue" value="0" />
    <input type="hidden" name="page" value="1" />
    <table class="main form me-no-box-shadow">
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
        <td>
          <label>
            <input type="checkbox" name="init"/>
            {{tr}}CGitlabCommit-action-Init mode{{/tr}}
          </label>
          <label>
            <input type="checkbox" name="bind" checked="checked"/>
            {{tr}}CGitlabCommit-action-Bind tasks{{/tr}}
          </label>
        </td>
      </tr>

      <tr>
        <td colspan="5" class="button">
          <button type="submit" class="import" onclick="Gitlab.importCommits(this.form, 1);">
            {{tr}}CGitlabCommit-action-Import{{/tr}}
          </button>
          <button id="import-commits-start" type="button" class="change" onclick="Gitlab.startCommitsImport();">
            {{tr}}CGitlabCommit-action-Mass import{{/tr}}
          </button>
          <button id="import-commits-stop" type="button" class="far fa-stop-circle" onclick="Gitlab.stopCommitsImport();" disabled>
            {{tr}}Pause{{/tr}}
          </button>
        </td>
      </tr>
    </table>
  </form>
</div>

{{* Results *}}
<div style="width: 49%; float: right;">
  <h3>{{tr}}sourceCode-legend-Gitlab API import results{{/tr}}</h3>
  <div id="import-gitlab-projects-results"></div>
  <div id="import-gitlab-branches-results"></div>
  <div id="import-gitlab-pipelines-results"></div>
  <div id="import-gitlab-commits-results"></div>
</div>

