/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

DSN = {
  load: function(dsn, container) {
    var url = new Url("system", "ajax_load_dsn");
    url.addParam("dsn", dsn);
    url.addParam("dsn_uid", $(container).id);
    url.requestUpdate(container);
  },
  create: function (form) {
    return onSubmitFormAjax(form, null, "config-dsn-create-" + $V(form.dsn));
  },
  test: function (dsn, target) {
    var url = new Url("system", "ajax_test_dsn");
    url.addParam("dsn", dsn);
    url.requestUpdate(target || ("dsn-status-" + dsn));
  },
  edit: function (dsn, container) {
    var url = new Url("system", "ajax_edit_dsn");
    url.addParam("dsn", dsn);
    url.requestModal(500, 400, {
      onClose: function(){
        DSN.load(dsn, container);
      }
    });
  },
  createDB: function(dsn, host){
    var url = new Url("system", "ajax_create_db");
    url.addParam("dsn", dsn);
    url.addParam("host", host);
    url.requestModal(500, 400);
  }
};