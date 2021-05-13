/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

CronJob = {
  edit : function(identifiant) {
    new Url("system", "ajax_edit_cronjob")
      .addParam("identifiant", identifiant)
      .requestModal()
      .modalObject.observe("afterClose", CronJob.refresh_list_cronjobs);
  },

  refresh_list_cronjobs : function () {
    new Url("system", "ajax_list_cronjobs")
      .requestUpdate("list_cronjobs");
  },

  changeField : function (element) {
    var value = true;
    if ($V(element) === "") {
      value = false;
    }
    var form = element.form;
    form._second.disabled = value;
    form._minute.disabled = value;
    form._hour.disabled   = value;
    form._day.disabled    = value;
    form._month.disabled  = value;
    form._week.disabled   = value;
  },

  refresh_logs : function (form) {
    new Url("system", "ajax_cronjobs_logs")
      .addFormData(form)
      .requestUpdate("search_log_cronjob");
  },

  changePageLog : function (page) {
    var form = getForm("search_cronjob");
    $V(form.page, page);
    CronJob.refresh_logs(form);
  },

  ChangeActive : function (form) {
    form.up(1).toggleClassName("opacity-30");
  },

  setServerAddress : function (element) {
    var tokenfield = new TokenField(element.form.servers_address);
    if ($V(element)) {
      tokenfield.add(element.value);
    }
    else {
      tokenfield.remove(element.value);
    }
  }
};