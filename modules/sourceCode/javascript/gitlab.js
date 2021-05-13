/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

Gitlab = window.Gitlab || {
  
  /**
   * Imports Gitlab projects from API
   */
  importProjects: function() {
    var url = new Url('sourceCode', 'ajax_import_gitlab_projects');
    url.requestUpdate("import-gitlab-projects-results");
  },
  
  /**
   * Imports Gitlab branches of ready projects from API
   */
  importProjectsBranches: function() {
    var url = new Url('sourceCode', 'ajax_import_gitlab_branches');
    url.requestUpdate("import-gitlab-branches-results");
  },

  /**
   * Imports Gitlab branches of ready pipelines from API
   */
  importProjectsPipelines: function() {
    var url = new Url('sourceCode', 'ajax_import_gitlab_pipelines');
    url.requestUpdate("import-gitlab-pipelines-results");
  },
  
  /**
   * Import Gitlab commits on a project branch
   *
   * @param form
   */
  importCommits: function(form, page) {
    $V(form.elements.a, "ajax_import_gitlab_commits");
    if (page) {
      $V(form.elements.page, page);
    }
    form.onsubmit();
  },
  
  nextCommitsImport: function(page) {
    let form = getForm("import-gitlab-commits-form");
    if (parseInt($V(form.elements['continue'])) === 0) {
      return;
    }
    if (page) {
      $V(form.elements['page'], parseInt(page));
    }
    this.importCommits(form);
  },
  
  startCommitsImport: function() {
    let form = getForm("import-gitlab-commits-form");
    $V(form.elements['continue'], 1);
    $V(form.elements['page'], 1);

    let btn_start = $('import-commits-start');
    btn_start.disable();
  
    let btn_stop = $('import-commits-stop');
    btn_stop.enable();
  
    this.importCommits(form);
  },
  
  stopCommitsImport: function() {
    let form = getForm("import-gitlab-commits-form");
    $V(form.elements['continue'], 0);
  
    let btn_start = $('import-commits-start');
    btn_start.enable();
  
    let btn_stop = $('import-commits-stop');
    btn_stop.disable();
  },
  
  /**
   * @param form
   * @param input_field
   * @param output_field
   */
  makeProjectAutocomplete: function (form, input_field, output_field) {
    var url = new Url('sourceCode', 'ajax_gitlab_project_autocomplete');
    url.addParam('input_field', input_field.name);
    url.addParam("view_field", "_view");
    url.autoComplete(
      input_field, null, {
        minChars:      2,
        dropdown:      true,
        method:        'get',
        callback:      function (input, queryString) {
          return queryString + '&ready=' + $V(form.elements.ready);
        },
        updateElement: function (selected) {
          var data = selected.down('.data_autocomplete');
          var _id = data.get('id');
          if (_id) {
            $V(output_field ? output_field : form.elements.project_id, _id);
            $V(input_field, selected.down('strong').getText());
            Gitlab.handleBranchAutocomplete(form, true);
          }
        }
      }
    );
  },
  
  /**
   * @param form
   * @param input_field
   * @param output_field
   */
  makeBranchAutocomplete: function (form, input_field, output_field) {
    var url = new Url('sourceCode', 'ajax_gitlab_branch_autocomplete');
    url.addParam('input_field', input_field.name);
    url.addParam("view_field", "_view");
    url.autoComplete(
      input_field, null, {
        minChars:      2,
        dropdown:      true,
        method:        'get',
        callback:      function (input, queryString) {
          return queryString + '&project_id=' + $V(form.elements.project_id);
        },
        updateElement: function (selected) {
          var data = selected.down('.data_autocomplete');
          var _id = data.get('id');
          if (_id) {
            $V(output_field ? output_field : form.elements.branch_id, _id);
            $V(input_field, selected.down('strong').getText());
          }
        }
      }
    );
  },
  
  searchCommits: function (form) {
    $V(form.elements.a, "ajax_search_gitlab_commits");
    form.onsubmit();
  },

  searchPipelines: function (form) {
    $V(form.elements.a, "ajax_search_gitlab_pipelines");
    form.onsubmit();
  },
  
  searchProjects: function (form) {
    $V(form.elements.a, "ajax_search_gitlab_projects");
    form.onsubmit();
  },
  
  orderCommits: function(form, order, way) {
    $V(form._order, order);
    $V(form._way, way);
    $V(form.elements.start, '0');
    this.searchCommits(form)
  },
  
  /**
   *
   * @param form
   * @param enable
   * @param reset
   * @param change
   */
  handleBranchAutocomplete: function(form, enable = false, reset = false, change = false) {
    if (change) {
      $V(form.elements.branch_id, '');
      $V(form.elements._branch_autocomplete, '');
    }
    if (reset) {
      $V(form.elements.project_id, '');
      $V(form.elements._project_autocomplete, '');
      form.elements._branch_autocomplete.disabled=true;
    }
    if (enable) {
      form.elements._branch_autocomplete.disabled=false;
    }
  },
  
  submitProject: function (form, callback) {
    callback = callback || Control.Modal.close;
    return onSubmitFormAjax(form, callback);
  },
  
  editProject: function (project_id, callback) {
    var url = new Url('sourceCode', 'ajax_edit_gitlab_project');
    url.addParam('project_id', project_id);
    
    if (callback !== false) {
      callback = callback || {
        onClose: function () {
          Gitlab.searchProjects(getForm('search-gitlab-projects-form'));
        }
      };
    }
    
    url.requestModal(800, 400, callback);
  },

  showJobClassReports: function (tests_report_id, callback) {
    var url = new Url('sourceCode', 'vw_gitlab_job_class_reports');
    url.addParam('tests_report_id', tests_report_id);
    url.requestModal("50%", "70%", callback);
  },

  searchJobClassReports: function (form) {
    $V(form.elements.a, "ajax_search_gitlab_job_class_reports");
    $V(form.elements.start, '0');
    form.onsubmit();
  },
};
