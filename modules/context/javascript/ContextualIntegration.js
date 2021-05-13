/**
 * @package Mediboard\Context
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

ContextualIntegration = {
  updateList: function(){
    var url = new Url("context", "ajax_list_integrations");
    url.requestUpdate("list-integrations");
  },
  create: function(){
    ContextualIntegration.edit(0);
  },
  edit: function(id){
    var url = new Url("context", "ajax_edit_integration");
    url.addNotNullParam("integration_id", id);
    url.requestUpdate("edit-integration", function(){
      $("row-CContextualIntegration-"+id).addUniqueClassName("selected");
    });
  },
  editCallback: function(id){
    ContextualIntegration.edit(id);
    ContextualIntegration.updateList();
  },
  toggleIconURL: function(form, focus) {
    var value = $V(form._icon_url_fa);
    var display = value == "";
    var container = $("icon-url-container");

    container.setVisible(display);

    if (display) {
      if ($V(form.icon_url).indexOf("fa-") === 0) {
        $V(form.icon_url, '');
      }
      if (focus) {
        form.icon_url.tryFocus();
      }
    }
    else {
      $V(form.icon_url, value);
    }
  },

  displayIcon: function(src) {
    if (!/^https?:\/\//.exec(src)) {
      return;
    }

    $('icon-url-container').down('img').src = src;
  },

  createLocation: function(integration_id){
    ContextualIntegration.editLocation(0, integration_id);
  },
  editLocation: function(id, integration_id){
    var url = new Url("context", "ajax_edit_integration_location");
    url.addNotNullParam("location_id", id);
    url.addNotNullParam("integration_id", integration_id);
    url.requestModal(400, 250);
  },
  editLocationCallback: function(id, obj){
    ContextualIntegration.edit(obj.integration_id);
  },
  insertPattern: function (pattern, url) {
    url.replaceInputSelection("%"+pattern+"%");
  },

  do_integration: function(element, unique_id){
    if (!element.hasClassName("contextual-trigger")) {
      element = element.up(".contextual-trigger");
    }

    var mode = element.get("display_mode");
    var urlString = element.get("url");
    var title = element.get("title");
    var url = new Url();

    switch (mode) {
      case "modal":
        url.modal({width: "100%", height: "100%", baseUrl: urlString, title: title});
        break;

      case "popup":
        url.popup("100%", "100%", 'Appel contextuel ' + unique_id, null, null, urlString);
        break;

      case "current_tab":
        url.redirect(urlString);
        break;

      case "new_tab":
        url.open(urlString);
        break;
    }
  }
};