{{*
 * @package Mediboard\Sante400
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if !$dialog}}
  {{mb_include module=system template=inc_pagination total=$total_idexs current=$page change_page='changePage' jumper=100}}
{{/if}}

<table class="tbl me-no-align">
  {{if $dialog}}
    <tr>
      <th colspan="6" class="title">
        {{if $target}}
          Identifiants pour '{{$target->_view}}' (#{{$target->_id}})
        {{else}}
          Identifiants
        {{/if}}
      </th>
    </tr>
  {{/if}}

  <tr>
    <th class="narrow button"></th>
    {{if !$dialog}}
      <th>{{tr}}CIdSante400-object_class{{/tr}}</th>
      <th>{{tr}}CIdSante400-object_id-court{{/tr}}</th>
      <th>{{tr}}CIdSante400-object{{/tr}}</th>
    {{/if}}
    <th class="narrow">{{tr}}CIdSante400-datetime_create{{/tr}}</th>
    <th class="narrow">{{tr}}CIdSante400-last_update{{/tr}}</th>
    <th>{{tr}}CIdSante400-id400-court{{/tr}}</th>
    <th>{{tr}}CIdSante400-tag{{/tr}}</th>
    <th>{{tr}}CIdSante400-_type{{/tr}}</th>
  </tr>

  {{foreach from=$idexs item=_idex}}
    <tr {{if $_idex->_id == $idex_id}}class="selected"{{/if}}>
      <td>
        <button class="edit notext" onclick="editId400('{{$_idex->_id}}', this)"></button>
      </td>
      {{if !$dialog}}
        <td>{{$_idex->object_class}}</td>
        <td>{{$_idex->object_id}}</td>
        <td>
          {{assign var="object" value=$_idex->_ref_object}}
          {{if $object->_id}}
            <span onmouseover="ObjectTooltip.createEx(this, '{{$object->_guid}}')">
              {{$object}}
            </span>
          {{else}}
            <div class="warning">Objet supprimé</div>
          {{/if}}
        </td>
      {{/if}}
      <td>
        {{$_idex->datetime_create|date_format:$conf.datetime}}
      </td>
      <td>
        {{$_idex->last_update|date_format:$conf.datetime}}
      </td>
      <td>{{$_idex->id400}}</td>
      <td>{{$_idex->tag}}</td>
      <td>
        {{if $_idex->_type}}
          <span class="idex-special idex-special-{{$_idex->_type}}">
          {{$_idex->_type}}
        </span>
        {{/if}}
      </td>
    </tr>
    {{foreachelse}}
    <tr>
      <td colspan="9" class="empty">
        {{tr}}CIdSante400.none{{/tr}}
      </td>
    </tr>
  {{/foreach}}
</table>
