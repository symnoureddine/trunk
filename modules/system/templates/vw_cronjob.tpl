{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=system script=cronjob ajax=true}}

<script>
  Main.add(function() {
    Control.Tabs.create('tabs-cronjob', true, {
      afterChange: function(container) {
        switch (container.id) {
          case "tab_list_cronjobs":
            CronJob.refresh_list_cronjobs();
            break;

          case "tab_log_cronjobs":
            getForm('search_cronjob').onsubmit();
            break;
        }
      }
    });
  });
</script>

<ul id="tabs-cronjob" class="control_tabs">
  <li><a href="#tab_list_cronjobs">{{tr}}CCronJob.list{{/tr}}</a></li>
  <li><a href="#tab_log_cronjobs">{{tr}}CCronJobLog{{/tr}}</a></li>
</ul>

<div id="tab_list_cronjobs" style="display: none;">
  <button class="new" type="button" onclick="CronJob.edit(0)">{{tr}}CCronJob.new{{/tr}}</button>
  <table class="tbl">
    <tr>
      <th class="title" colspan="7">{{tr}}CCronJob{{/tr}}</th>
      <th class="title" colspan="5" style="width: 50%">Execution</th>
    </tr>
    <tr>
      <th>{{mb_title class="CCronJob" field="active"}}</th>
      <th>{{mb_title class="CCronJob" field="name"}}</th>
      <th>{{mb_title class="CCronJob" field="description"}}</th>
      <th>{{mb_title class="CCronJob" field="params"}}</th>
      <th>{{mb_title class="CCronJob" field="token_id"}}</th>
      <th>{{mb_title class="CCronJob" field="execution"}}</th>
      <th>{{mb_title class="CCronJob" field="servers_address"}}</th>
      <th class="narrow" style="text-align: center;">
        <i class="fas fa-circle-notch fa-lg" title="{{tr}}CCronJob-title-_lasts_executions{{/tr}}"></i>
      </th>
      <th>n</th>
      <th>n+1</th>
      <th>n+2</th>
      <th>n+3</th>
      <th>n+4</th>
    </tr>

    <tbody id="list_cronjobs"></tbody>
  </table>
</div>

<div id="tab_log_cronjobs">
  <form name="search_cronjob" method="post" onsubmit="return onSubmitFormAjax(this, CronJob.refresh_logs(this))">
    <input type="hidden" name="page">
    <table class="form">
      <tr>
        <th>{{mb_title object=$log_cron field="cronjob_id"}}</th>
        <td>{{mb_field object=$log_cron field="cronjob_id" canNull=true form="search_cronjob" autocomplete="true,1,50,true,true"}}</td>

        <th>{{mb_title object=$log_cron field="status"}}</th>
        <td>{{mb_field object=$log_cron field="status" canNull=true emptyLabel="Choose"}}</td>

        <th>{{mb_title object=$log_cron field="severity"}}</th>
        <td>{{mb_field object=$log_cron field="severity" canNull=true emptyLabel="Choose"}}</td>

        <th>Du</th>
        <td>{{mb_field object=$log_cron field="_date_min" form="search_cronjob" register=true}}</td>

        <th>jusqu'au</th>
        <td>{{mb_field object=$log_cron field="_date_max" form="search_cronjob" register=true}}</td>
      </tr>

      <tr>
        <td colspan="10" class="button"><button type="submit" class="search">{{tr}}Search{{/tr}}</button></td>
      </tr>
    </table>
  </form>

  <div id="search_log_cronjob"></div>
</div>