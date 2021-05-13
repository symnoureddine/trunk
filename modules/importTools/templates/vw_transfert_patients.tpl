{{*
 * @package Mediboard\ImportTools
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=system script=object_selector ajax=true}}

<script>
  goToExportGroup = function() {
    var form = getForm("export_group_id");
    var id = form.object_id.value;
    var url = new Url("dPetablissement", "do_export_group", "raw");
    if (id) {
      url.addParam("group_id", id);
    }
    url.open();
  };

  executeRedirectImportExport = function(redirect, module) {
    var url = new Url(module, redirect);
    url.open();
  };

  emptyFieldGroup = function() {
    var form = getForm("export_group_id");
    form.object_id.value = "";
    form.object_view.value="";
  };

  ObjectSelector.init = function () {
    this.sForm = "export_group_id";
    this.sView = "object_view";
    this.sId = "object_id";
    this.sClass = "object_class";
    this.onlyclass = "true";
    this.pop();
  }

</script>

<br/><br/>

<table class="main form">
  <tr>
    <td class="narrow">
      <form method="get" id="export_group_id">
        <label for="input_group_id">{{tr}}importTools-label-export-group{{/tr}}</label>
        <input type="hidden" name="object_id" value=""/>
        <input type="hidden" name="object_class" value="CGroups"/>
        <input id="input_group_id" type="text" name="object_view" readonly="readonly" value=""/>
        <button type="button" onclick="ObjectSelector.init()" class="search notext">{{tr}}Search{{/tr}}</button>
        <button type="button" class="notext cancel" onclick="emptyFieldGroup()">{{tr}}Empty{{/tr}}</button>
      </form>
    </td>
    <td>
      <button class="fa fa-upload me-primary" onclick="goToExportGroup()">{{tr}}importTools-export-group{{/tr}}</button>
    </td>
  </tr>
  <tr>
    <td class="narrow"></td>
    <td>
      <button class="fa fa-download" onclick="executeRedirectImportExport('vw_import_group', 'etablissement')">{{tr}}importTools-import-group{{/tr}}</button>
    </td>
  </tr>
  <tr>
    <td class="narrow"></td>
    <td>
      <button class="fa fa-upload" onclick="executeRedirectImportExport('vw_export_patients', 'patients')">{{tr}}importTools-export-patients{{/tr}}</button>
    </td>
  </tr>
  <tr>
    <td class="narrow"></td>
    <td>
      <button class="fa fa-download" onclick="executeRedirectImportExport('vw_import_patients', 'patients')">{{tr}}importTools-import-patients{{/tr}}</button>
    </td>
  </tr>
</table>



