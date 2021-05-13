{{*
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=eai script=transformation ajax=true}}

<fieldset>
  <legend>{{tr}}{{$message}}{{/tr}} <span class="compact">({{tr}}{{$message}}-desc{{/tr}})</span></legend>
  
  <table class="tbl form me-no-box-shadow me-no-align">
  {{foreach from=$messages_supported item=_message_supported}}
    {{assign var=event      value=$_message_supported->_event}}
    {{assign var=event_name value=$event|getShortName}}

    <tr>
      <td class="narrow">
        <button class="fa fa-magic notext"
                onclick="EAITransformation.list('{{$message}}', '{{$event_name}}', '{{$actor_guid}}')">
          {{tr}}CTransformation{{/tr}}</button>
      </td>
      <td style="vertical-align: middle;" class="narrow"><strong>{{tr}}{{$_message_supported->message}}{{/tr}}</strong></td>
      <td style="vertical-align: middle;" class="narrow"> <i class="fa fa-arrow-right"></i></td>
      <td style="vertical-align: middle;" class="text compact">{{tr}}{{$_message_supported->message}}-desc{{/tr}}</td>
    </tr>
  {{/foreach}}
  </table>
</fieldset>
