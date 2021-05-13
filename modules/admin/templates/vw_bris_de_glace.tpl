{{*
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    var oform = getForm("list_bris");
    Calendar.regField(oform.date_start);
    Calendar.regField(oform.date_end);
    oform.onsubmit();
  });
</script>

<form name="list_bris" method="get" onsubmit="return onSubmitFormAjax(this, null, 'result_bris')">
  <input type="hidden" name="page" value="0"/>
  <input type="hidden" name="m" value="admin"/>
  <input type="hidden" name="a" value="ajax_search_bris_by_user" />

  <select name="object_class" onchange="this.form.onsubmit();">
    <option value="">&mdash; Types</option>
    <option value="CSejour" selected="selected">Séjours</option>
  </select>

  <label>Début :
    <input type="hidden" name="date_start" value="{{$date_start}}" onchange="this.form.onsubmit();"/>
  </label>
  <label>Fin :
    <input type="hidden" name="date_end" value="{{$date_end}}" onchange="this.form.onsubmit();"/>
  </label>

  <button class="change notext">{{tr}}Refresh{{/tr}}</button>
</form>

<table class="tbl">
  <thead>
    <tr>
      <th>{{mb_title object=$bris field=user_id}}</th>
      <th>{{mb_title object=$bris field=object_id}}</th>
      <th>{{mb_title object=$bris field=comment}}</th>
      <th class="narrow">{{mb_title object=$bris field=date}}</th>
    </tr>
  </thead>
  <tbody id="result_bris"></tbody>
</table>