{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_include module=system template=CMbObject_edit}}

{{if $object->_id}}
<script type="text/javascript">
  Main.add(function(){
    Control.Tabs.create("ex-list-tabs", true);
  });
</script>

<ul id="ex-list-tabs" class="control_tabs me-align-auto">
  <li>
    <a href="#ex-back-list_items" {{if $object->_back.list_items|@count == 0}} class="empty" {{/if}}>{{tr}}CExList-back-list_items{{/tr}} <small>({{$object->_back.list_items|@count}})</small></a>
  </li>
  <li>
    <a href="#ex-back-concepts" {{if $object->_back.concepts|@count == 0}} class="empty" {{/if}}>{{tr}}CExList-back-concepts{{/tr}} <small>({{$object->_back.concepts|@count}})</small></a>
  </li>
</ul>

<div id="ex-back-list_items" style="display: none;" class="me-padding-0 me-align-auto">
  {{mb_include module=forms template=inc_ex_list_item_edit context=$object}}
</div>

<div id="ex-back-concepts" style="display: none;" class="me-padding-0 me-align-auto">
  <table class="main tbl me-no-box-shadow me-no-align">
    <tr>
      <th>
        {{mb_title class=CExConcept field=name}}
      </th>
      <th>
        {{mb_title class=CExConcept field=prop}}
      </th>
    </tr>
    
    {{foreach from=$object->_back.concepts item=_concept}}
      <tr>
        <td>
          {{mb_value object=$_concept field=name}}
        </td>
        <td>
          {{mb_value object=$_concept field=prop}}
        </td>
      </tr>
    {{foreachelse}}
      <tr>
        <td class="empty" colspan="2">{{tr}}CExConcept.none{{/tr}}</td>
      </tr>
    {{/foreach}}
  </table>
</div>
{{/if}}