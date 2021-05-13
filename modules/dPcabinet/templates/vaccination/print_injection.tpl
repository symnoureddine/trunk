{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    window.print();
  });
</script>
<table id="vaccination_print" class="main tbl">
  <tr><th class="title" colspan="5">Liste des injections</th></tr>
  <tr>
    <th>{{tr}}Date{{/tr}}</th>
    <th>{{tr}}CInjection-speciality{{/tr}}</th>
    <th>{{tr}}CInjection-batch{{/tr}}</th>
    <th>{{tr}}CVaccin{{/tr}}</th>
    <th>{{tr}}CInjection-remarques{{/tr}}</th>
  </tr>
  {{foreach from=$injections item=_injection}}
    <tr>
      <td>{{$_injection->injection_date|date_format:$conf.date}}</td>
      {{if in_array($_injection->_id, $vaccinated)}}
        <td>{{$_injection->speciality}}</td>
        <td>{{$_injection->batch}}</td>
      {{else}}
        <td colspan="2">{{tr}}No{{/tr}} {{tr}}CVaccination-verb{{/tr}}</td>
      {{/if}}
      <td>
        <ul>
            {{foreach from=$_injection->_ref_vaccinations item=_vaccination}}
              <li>{{$_vaccination->_ref_vaccine->longname}}</li>
            {{/foreach}}
        </ul>
      </td>
      <td>{{$_injection->remarques}}</td>
    </tr>
  {{foreachelse}}
    <tr><td class="empty" colspan="5">{{tr}}common-msg-No result{{/tr}}</td></tr>
  {{/foreach}}
</table>
