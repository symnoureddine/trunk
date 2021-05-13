{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{foreach from=$cronjobs item=_cronjob}}
  <tr {{if !$_cronjob->active}}class="opacity-30"{{/if}}>
    <td class="narrow">
      <form name="editactive_{{$_cronjob->_id}}" method="post" action="?" onsubmit="return onSubmitFormAjax(this, CronJob.ChangeActive(this))">
        {{mb_class object=$_cronjob}}
        {{mb_key object=$_cronjob}}
        {{mb_field object=$_cronjob field="active" canNull=true onchange="this.form.onsubmit()"}}
      </form>
    </td>
    <td class="narrow">
      <button class="edit notext compact" type="button" onclick="CronJob.edit('{{$_cronjob->_id}}')">{{tr}}Modify{{/tr}}</button>
      {{mb_value object=$_cronjob field="name"}}
    </td>
    <td class="text compact">{{mb_value object=$_cronjob field="description"}}</td>
    <td class="text compact">{{mb_value object=$_cronjob field="params"}}</td>
    <td class="text compact">
      {{if $_cronjob->_token}}
        <span onmouseover="ObjectTooltip.createEx(this, '{{$_cronjob->_token->_guid}}');">{{$_cronjob->_token->label}}</span>
      {{/if}}
    </td>
    <td style="font-family: monospace">{{mb_value object=$_cronjob field="execution"}}</td>
    <td>
      {{if $_cronjob->servers_address}}
        {{mb_value object=$_cronjob field="servers_address"}}
      {{else}}
        {{tr}}All{{/tr}}
      {{/if}}
    </td>

    <td>
      {{math assign=div equation="x+y" x=$_cronjob->_lasts_status.ok y=$_cronjob->_lasts_status.ko}}
      {{if $div != 0}}
        {{math assign=ratio equation="(x*100)/y" x=$_cronjob->_lasts_status.ok y=$div}}

        <script>
          Main.add(function() {
            ProgressMeter.init('cronjob-execution-{{$_cronjob->_id}}', '{{$ratio}}');
          });
        </script>

        <div id="cronjob-execution-{{$_cronjob->_id}}" style="width: 20px; height: 20px;"
             title="{{$ratio}} % ({{$_cronjob->_lasts_status.ok}} / {{$div}})">
        </div>
      {{/if}}
    </td>

    {{foreach from=$_cronjob->_next_datetime item=_next_datetime}}
      <td style="text-align: right">
        {{if $_next_datetime|iso_date == $dnow}}
          {{$_next_datetime|date_format:$conf.time}}
        {{else}}
          {{$_next_datetime|date_format:$conf.datetime}}
        {{/if}}
      </td>
    {{foreachelse}}
      <td class="narrow"></td>
      <td class="narrow"></td>
      <td class="narrow"></td>
      <td class="narrow"></td>
      <td class="narrow"></td>
    {{/foreach}}
  </tr>
  {{foreachelse}}
  <tr>
    <td class="empty" colspan="11">{{tr}}CCronJob.none{{/tr}}</td>
  </tr>
{{/foreach}}