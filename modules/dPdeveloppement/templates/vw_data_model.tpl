{{*
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=dPdeveloppement script=data_model ajax=true}}

<script>
  Main.add(function () {
    var form = getForm('filter_class');
    form.onsubmit();
    $(form.object_class).makeAutocomplete({width: "200px"});
  });

  function emptyClass(form) {
    $V(form.object_class, 0);
    $V(form.object_class.up('td').down('input'), '');
    $V(form.object_id, '');
  }
</script>

<form name="filter_class" method="get" onsubmit="return onSubmitFormAjax(this, null, 'graph_draw');">
  <input type="hidden" name="m" value="dPdeveloppement"/>
  <input type="hidden" name="a" value="ajax_vw_data_model"/>

  <table class="main form">
    <col style="width: 10%;"/>

    <tr>
      <th>{{mb_label object=$data_model field=class_select}}</th>
      <td>
        <select name="object_class" class="str" style="width: 200px;" onchange='this.form.onsubmit();'>
          {{foreach from=$all_classes item=curr_class}}
            <option value="{{$curr_class}}" {{if $data_model->class_select == $curr_class}}selected{{/if}}>
              {{tr}}{{$curr_class}}{{/tr}} - {{$curr_class}}
            </option>
          {{/foreach}}
        </select>
        <button type="button" class="cancel notext" onclick="emptyClass(this.form)"></button>
      </td>
      <th>{{mb_label object=$data_model field=show_props}}</th>
      <td>{{mb_field object=$data_model field=show_props onchange='this.form.onsubmit();'}}</td>

      <th>{{mb_label object=$data_model field=show_backprops}}</th>
      <td>{{mb_field object=$data_model field=show_backprops onchange='this.form.onsubmit();'}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$data_model field=hierarchy_sort}}</th>
      <td>{{mb_field object=$data_model field=hierarchy_sort onchange='this.form.onsubmit();'}}</td>

      <th>{{mb_label object=$data_model field=number}}</th>
      <td>{{mb_field object=$data_model field=number onchange='this.form.onsubmit();' form='filter_class' increment=true}}</td>

      <th>{{mb_label object=$data_model field=show_hover}}</th>
      <td>{{mb_field object=$data_model field=show_hover onchange='this.form.onsubmit();'}}</td>
    </tr>
  </table>
</form>

<div id="graph_draw"></div>