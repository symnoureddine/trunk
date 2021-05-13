{{*
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    Calendar.regField(getForm('changeDate').date_interv, null, {noView: true});
  });
</script>

<table class="main">
  <tr>
    <th class="button">
      <a href="?m={{$m}}&tab={{$tab}}&date_interv={{$prec}}" style="float: left;">&lt;&lt;&lt;</a>
      <a href="?m={{$m}}&tab={{$tab}}&date_interv={{$suiv}}" style="float: right">&gt;&gt;&gt;</a>
      <form name="changeDate" method="get">
        <input type="hidden" name="m" value="{{$m}}" />
        <input type="hidden" name="tab" value="{{$tab}}" />
        {{$date_interv|date_format:$conf.longdate}}
        <input type="hidden" name="date_interv" class="date" value="{{$date_interv}}" onchange="this.form.submit()" />
      </form>
    </th>
  </tr>
  
  <tr>
    <td colspan="3">
      <table class="tbl">
        <tr>
          <th class="title" colspan="6">
            Analyse du remplissage des côtés
          </th>
        </tr>
  
        <tr>
          <th>Date</th>
          <th>DHE</th>
          <th>Admission</th>
          <th>Consult. anesth.</th>
          <th>Services</th>
          <th>Bloc</th>
        </tr>

        {{foreach from=$listIntervs item=_interv}}
        <tr>
          <td>{{$_interv->_view}}</td>
          <td>
            <strong>{{mb_value object=$_interv field="cote"}}</strong>
          </td>
          <td class="{{if !$_interv->cote_admission}}warning{{elseif $_interv->cote_admission != $_interv->cote}}error{{else}}ok{{/if}}">
            {{mb_value object=$_interv field="cote_admission"}}
          </td>
          <td class="{{if !$_interv->cote_consult_anesth}}warning{{elseif $_interv->cote_consult_anesth != $_interv->cote}}error{{else}}ok{{/if}}">
            {{mb_value object=$_interv field="cote_consult_anesth"}}
          </td>
          <td class="{{if !$_interv->cote_hospi}}warning{{elseif $_interv->cote_hospi != $_interv->cote}}error{{else}}ok{{/if}}">
            {{mb_value object=$_interv field="cote_hospi"}}
          </td>
          <td class="{{if !$_interv->cote_bloc}}warning{{elseif $_interv->cote_bloc != $_interv->cote}}error{{else}}ok{{/if}}">
            {{mb_value object=$_interv field="cote_bloc"}}
          </td>
        </tr>
        {{foreachelse}}
        <tr>
          <td colspan="6" class="empty">{{tr}}None{{/tr}}</td>
        </tr>
        {{/foreach}}
      </table>
    </td>
  </tr>  
</table>

