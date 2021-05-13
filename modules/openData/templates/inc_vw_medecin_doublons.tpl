{{*
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<div id="display-medecins-doublons">
  <table class="main tbl">
    <tr>
      <td colspan="2">
        {{mb_include module=system template=inc_pagination change_page='ImportMedecins.changePageDoublon' total=$total current=$start step=$step}}
      </td>
    </tr>
    <tr>
      <th>
        {{tr}}CMedecinImport-doublon-key{{/tr}}
      </th>
      <th>
        {{tr}}CMedecinImport-doublon-medecin|pl{{/tr}}
      </th>
    </tr>

    {{foreach from=$doublons key=_key item=_dbls}}
      {{assign var=idx value=0}}
      {{foreach from=$_dbls item=_med}}
        <tr>
          {{if $idx < 1}}
            <td rowspan="{{$_dbls|@count}}" class="narrow compact">
              {{$_key}}
            </td>
          {{/if}}
          <td>
            <span onmouseover="ObjectTooltip.createEx(this, '{{$_med->_guid}}')">{{$_med}}</span>
          </td>

          {{assign var=idx value=$idx+1}}
        </tr>
      {{/foreach}}

      {{foreachelse}}
      <tr>
        <td class="empty">{{tr}}CMedecinImport-doublon.none{{/tr}}</td>
      </tr>
    {{/foreach}}
  </table>
</div>