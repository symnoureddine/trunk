/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

TestFHIR = {
  showPDQmRequest: function () {
    new Url("fhir", "ajax_fhir_search")
      .addParam("search_type", "CPDQm")
      .requestUpdate("test_fhir_pdqm");
  },

  showPIXmRequest: function () {
    new Url("fhir", "ajax_fhir_search")
      .addParam("search_type", "CPIXm")
      .requestUpdate("test_fhir_pixm");
  },

  showMHDRequest: function () {
    new Url("fhir", "ajax_fhir_search")
      .addParam("search_type", "CMHD")
      .requestUpdate("test_fhir_mhd");
  },

  showFHIRResources: function () {
    new Url("fhir", "ajax_fhir_resources")
      .requestUpdate("test_fhir_resources");
  },

  request: function (form, search_type) {
    new Url("fhir", "ajax_request_fhir")
      .addFormData(form)
      .addParam("search_type", search_type)
      .requestUpdate("request_" + search_type);

    return false;
  },

  requestWithURI: function (uri, search_type) {
    new Url("fhir", "ajax_request_fhir")
      .addParam("uri", uri)
      .addParam("search_type", search_type)
      .requestUpdate("request_" + search_type);

    return false;
  },

  readPDQm: function (id, format) {
    new Url("fhir", "ajax_request_fhir")
      .addParam("search_type", "CPDQm")
      .addParam("response_type", format)
      .addParam("id", id)
      .requestModal(900, 400);

    return false;
  },

  crudOperations: function (resource_type, crud, resource_id, version_id, contents) {
    var url = new Url("fhir", "ajax_crud_operations")
      .addParam("crud"         , crud)
      .addParam("resource_type", resource_type)
      .addParam("resource_id"  , resource_id)
      .addParam("version_id"   , version_id)
      .addParam("contents"     , contents);

    var form_options = getForm('request_options_fhir');
    var response_type = form_options.elements.response_type;
    if (response_type && $V(response_type)) {
      url.addParam("response_type", $V(response_type));
    }

    url.requestUpdate("result_crud_operations");

    return false;
  },

  capabilityStatement: function () {
    new Url("fhir", "ajax_show_capability_statement")
      .requestUpdate("result_crud_operations");

    return false;
  },

  showDocument : function (fhir_resource_id) {
    new Url("fhir", "ajax_show_document")
      .addParam("fhir_resource_id", fhir_resource_id)
      .requestModal("80%", "80%");
  },

  getFilesFromNDA : function(form, search_type) {
    new Url("fhir", "ajax_get_files_form_nda")
      .addFormData(form)
      .addParam("search_type", search_type)
      .requestUpdate("list_files_from_nda");

    return false;
  }
};
