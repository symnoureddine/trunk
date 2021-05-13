{{*
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    Control.Tabs.setTabCount('documents', '{{$crs|@count}}');
  });
</script>

<table class="tbl me-no-align me-no-box-shadow">
  <tr>
    <th class="narrow"></th>
    <th>Document</th>
    <th>Patient</th>
    <th>Contexte</th>
  </tr>

  {{foreach from=$affichageDocs item=_chapitre}}
    <tr>
      <th class="section" colspan="4">{{$_chapitre.name}}</th>
    </tr>
    {{foreach from=$_chapitre.items item=_cr}}
    <tr>
      <td>
        <button type="button" class="edit notext" onclick="Document.edit('{{$_cr->_id}}');" title="{{tr}}Edit{{/tr}}"></button>
      </td>
      <td class="text">
        {{mb_value object=$_cr field=nom}}
      </td>
      <td class="text">
        {{assign var=patient value=$_cr->_ref_patient}}
        <span onmouseover="ObjectTooltip.createEx(this, '{{$patient->_guid}}')">
          {{$patient}}
        </span>
      </td>
      <td class="text">
        {{assign var=contexte value=$_cr->_ref_object}}
        <span onmouseover="ObjectTooltip.createEx(this, '{{$contexte->_guid}}')">
          {{$contexte}}
        </span>
      </td>
    </tr>
    {{foreachelse}}
    <tr>
      <td colspan="4" class="empty">{{tr}}CCompteRendu.none{{/tr}}</td>
    </tr>
    {{/foreach}}
  {{foreachelse}}
  <tr>
    <td colspan="4" class="empty">{{tr}}CCompteRendu.none{{/tr}}</td>
  </tr>
  {{/foreach}}
</table>