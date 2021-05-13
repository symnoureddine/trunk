{{*
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=compteRendu script=tools}}

{{mb_default var=start value=0}}

<table class="tbl">
  <tr>
    <td class="halfPane" style="vertical-align: top;">
      <button type="button" class="search" onclick="Tools.regenerateFiles();">{{tr}}CCompteRendu-Regenerate pdf{{/tr}}</button>

      <label>
        <input type="text" size="5" id="start_regenerate" value="{{$start}}" />
      </label>

      <label>
        <input type="checkbox" id="auto_regenerate" checked /> {{tr}}Auto{{/tr}}
      </label>
    </td>
    <td>
      <div id="regenerate_area">

      </div>
    </td>
  </tr>
  <tr>
    <td>
      <select id="object_class_field">
        {{foreach from=$object_classes key=_object_class item=_object_class_name}}
          <option value="{{$_object_class}}">{{tr}}{{$_object_class_name}}{{/tr}}</option>
        {{/foreach}}
      </select>

      <button type="button" class="download" onclick="Tools.exportFields();">{{tr}}CTemplateManager-Export fields{{/tr}}</button>
    </td>
  </tr>
</table>
