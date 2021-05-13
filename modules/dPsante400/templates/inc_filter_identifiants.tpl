{{*
 * @package Mediboard\Sante400
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="filterFrm" action="?" method="get"
      onsubmit="return onSubmitFormAjax(this, null, 'list_identifiants')">
  <input type="hidden" name="m" value="dPsante400"/>
  <input type="hidden" name="a" value="ajax_list_identifiants"/>
  <input type="hidden" name="page" value="{{$page}}" onchange="this.form.onsubmit()"/>

  <table class="main layout">
    <tr>
      <td class="separator expand" onclick="MbObject.toggleColumn(this, $(this).next())"></td>

      <td>
        <table class="form">
          <tr>
            <td>{{mb_label object=$filter field="object_class"}}</td>
            <td>
              <select name="object_class" class="str maxLength|25">
                <option value="">&mdash; Toutes les classes</option>
                {{foreach from=$listClasses item=curr_class}}
                  <option value="{{$curr_class}}" {{if $curr_class == $filter->object_class}}selected="selected"{{/if}}>
                    {{$curr_class}}
                  </option>
                {{/foreach}}
              </select>
            </td>

            <td>{{mb_label object=$filter field="object_id"}}</td>
            <td>
              <input name="object_id" class="ref" value="{{$filter->object_id}}"/>
              <button class="search" type="button" onclick="ObjectSelector.initFilter()">{{tr}}Search{{/tr}}</button>
              <script type="text/javascript">
                ObjectSelector.initFilter = function () {
                  this.sForm = "filterFrm";
                  this.sId = "object_id";
                  this.sClass = "object_class";
                  this.onlyclass = "false";
                  this.pop();
                }
              </script>
            </td>
          </tr>

          <tr>
            <td>{{mb_label object=$filter field="id400"}}</td>
            <td>{{mb_field object=$filter field="id400" canNull=true}}</td>
            <td>{{mb_label object=$filter field="tag"}}</td>
            <td>{{mb_field object=$filter field="tag" size=30}}</td>
          </tr>

          <tr>
            <td colspan="4" class="button">
              <button type="submit" class="search">{{tr}}Filter{{/tr}}</button>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</form>