{{*
 * @package Mediboard\Pmsi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  function reloadSortieLine(sejour_id) {
    var url = new Url("admissions", "ajax_sortie_line");
    url.addParam("sejour_id", sejour_id);
    url.requestUpdate("CSejour-"+sejour_id);
  }

  Main.add(function() {
    Admissions.restoreSelection();
    Calendar.regField(getForm("changeDateSorties").date, null, {noView: true});
    Prestations.callback = reloadSorties;
  });
</script>

{{mb_include module=admissions template=inc_refresh_page_message}}

{{if $period}}
  <div class="small-info">
    Vue partielle limitée au <strong>{{$period}}</strong>. Veuillez changer le filtre pour afficher toute la journée.
  </div>
{{/if}}

<table class="tbl" id="sortie">
  <tr>
    <th class="title" colspan="10">
      <a href="#1" onclick="$V(getForm('selType').date, '{{$hier}}'); reloadFullSorties()" style="display: inline">&lt;&lt;&lt;</a>
      {{$date|date_format:$conf.longdate}}
      <form name="changeDateSorties" action="?" method="get">
        <input type="hidden" name="date" class="date" value="{{$date}}" onchange="$V(getForm('selType').date, this.value); reloadFullSorties()" />
      </form>
      <a href="#1" onclick="$V(getForm('selType').date, '{{$demain}}'); reloadFullSorties()"  style="display: inline">&gt;&gt;&gt;</a>

      <br />

      <em style="float: left; font-weight: normal;">
        {{$sejours|@count}}
        {{if $selSortis == "n"}}sorties non effectuées
        {{elseif $selSortis == "nf"}}sorties non facturées
        {{else}}sorties ce jour
        {{/if}}
      </em>

      <select style="float: right" name="filterFunction" style="width: 16em;" onchange="$V(getForm('selType').filterFunction, this.value); reloadSorties();">
        <option value=""> &mdash; Toutes les fonctions</option>
        {{mb_include module="mediusers" template="inc_options_function" list=$functions selected=$filterFunction}}
      </select>

      {{if $type == "ambu" || $type == "exte" }}
        <button class="print" type="button" onclick="printAmbu('{{$type}}')">{{tr}}Print{{/tr}} {{tr}}CSejour.type.{{$type}}{{/tr}}</button>
      {{/if}}
    </th>
  </tr>

  <tr>
    <th>
      <input type="checkbox" style="float: left;" onclick="Admissions.togglePrint('sortie', this.checked)"/>
      {{mb_colonne class="CSejour" field="patient_id" order_col=$order_col order_way=$order_way function=sortBy}}
    </th>
    <th class="narrow">
      <input type="text" size="3" onkeyup="Admissions.filter(this, 'sortie')" id="filter-patient-name" />
    </th>
    <th> {{tr}}Date{{/tr}} d'entrée
    </th>
    <th>
      {{mb_colonne class="CSejour" field="sortie_prevue" order_col=$order_col order_way=$order_way function=sortBy}}
    </th>
    <th style="width: 20%">{{tr}}Actions{{/tr}}</th>
  </tr>

  {{foreach from=$sejours item=_sejour}}
    <tr class="sejour-type-default sejour-type-{{$_sejour->type}} {{if !$_sejour->facturable}} non-facturable {{/if}}" id="{{$_sejour->_guid}}">
      {{mb_include module="pmsi" template="inc_vw_sortie_line" nodebug=true}}
    </tr>
  {{/foreach}}
</table>