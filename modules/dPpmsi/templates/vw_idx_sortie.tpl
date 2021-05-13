{{*
 * @package Mediboard\Pmsi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=admissions  script=admissions}}
{{mb_script module=compteRendu script=document}}
{{mb_script module=compteRendu script=modele_selector}}
{{mb_script module=files     script=file}}
{{mb_script module=planningOp  script=sejour}}
{{mb_script module=planningOp  script=prestations}}
{{if "web100T"|module_active}}
  {{mb_script module=web100T script=web100T}}
{{/if}}

<script>
  var sejours_enfants_ids;

  function printAmbu(type) {
    var form = getForm("selType");
    var url = new Url("admissions", "print_ambu");
    url.addParam("date", $V(form.date));
    url.addParam("type", type);
    url.popup(800, 600, "Ambu");
  }

  function printPlanning() {
    var form = getForm("selType");
    var url = new Url("admissions", "print_sorties");
    url.addParam("date"      , $V(form.date));
    url.addParam("type"      , $V(form._type_admission));
    url.addParam("service_id", [$V(form.service_id)].flatten().join(","));
    url.addParam("period"    , $V(form.period));
    url.popup(700, 550, "Sorties");
  }

  function printDHE(type, object_id) {
    var url = new Url("planningOp", "view_planning");
    url.addParam(type, object_id);
    url.popup(700, 550, "DHE");
  }

  function changeEtablissementId(form) {
    $V(form._modifier_sortie, '0');
    var type = $V(form.type);
    submitSortie(form, type);
  };

  function reloadFullSorties() {
    var form = getForm("selType");
    var url = new Url("admissions", "httpreq_vw_all_sorties");
    url.addParam("date"      , $V(form.date));
    url.addParam("selSortis" , $V(form.selSortis));
    url.addParam("type"      , $V(form._type_admission));
    url.addParam("service_id", [$V(form.service_id)].flatten().join(","));
    url.addParam("prat_id"   , $V(form.prat_id));
    url.addParam("current_m" , App.m);
    url.requestUpdate('allSorties');
    reloadSorties();
  }

  function reloadSorties() {
    var form = getForm("selType");
    var url = new Url("pmsi", "httpreq_vw_sorties");
    url.addParam("date"      , $V(form.date));
    url.addParam("selSortis" , $V(form.selSortis));
    url.addParam("order_col" , $V(form.order_col));
    url.addParam("order_way" , $V(form.order_way));
    url.addParam("type"      , $V(form._type_admission));
    url.addParam("service_id", [$V(form.service_id)].flatten().join(","));
    url.addParam("prat_id"   , $V(form.prat_id));
    url.addParam("period"    , $V(form.period));
    url.addParam("filterFunction" , $V(form.filterFunction));
    url.requestUpdate("listSorties");
  }

  function reloadSortiesDate(elt, date) {
    var form = getForm("selType");
    $V(form.date, date);
    var old_selected = elt.up("table").down("tr.selected");
    old_selected.select('td').each(function(td) {
      // Supprimer le style appliqué sur le nombre d'admissions
      var style = td.readAttribute("style");
      if (/bold/.match(style)) {
        td.writeAttribute("style", "");
      }
    });
    old_selected.removeClassName("selected");

    // Mettre en gras le nombre d'admissions
    var elt_tr = elt.up("tr");
    elt_tr.addClassName("selected");
    var pos = 1;
    if ($V(form.selSortis) == 'n') {
      pos = 2;
    }
    var td = elt_tr.down("td", pos);
    td.writeAttribute("style", "font-weight: bold");

    reloadSorties();
  }

  function reloadSortieLine(sejour_id) {
    var url = new Url("admissions", "ajax_sortie_line");
    url.addParam("sejour_id", sejour_id);
    url.requestUpdate("CSejour-"+sejour_id);
  }

  function updateModeSortie(select) {
    var selected = select.options[select.selectedIndex];
    var form = select.form;
    $V(form.elements.mode_sortie, selected.get("mode"));
  };

  function sortBy(order_col, order_way) {
    var form = getForm("selType");
    $V(form.order_col, order_col);
    $V(form.order_way, order_way);
    reloadSorties();
  }

  function filterAdm(selSortis) {
    var form = getForm("selType");
    $V(form.selSortis, selSortis);
    reloadFullSorties();
  }

  function selectServices(view, services_ids_suggest) {
    var url = new Url("pmsi", "ajax_select_services");

    if (Object.isUndefined(view)) {
      view = this.tabs.activeLink.key;
    }

    if (!Object.isUndefined(services_ids_suggest)) {
      url.addParam("services_ids_suggest", services_ids_suggest);
    }

    url.addParam("view", view);
    url.requestModal(null, null, {maxHeight: "90%"});
  }

  Main.add(function() {
    Admissions.table_id = "listSorties";
    var totalUpdater = new Url("admissions", "httpreq_vw_all_sorties");
    totalUpdater.addParam("current_m" , App.m);
    Admissions.totalUpdater = totalUpdater.periodicalUpdate('allSorties', { frequency: 120 });

    var listUpdater = new Url("pmsi", "httpreq_vw_sorties");
    Admissions.listUpdater = listUpdater.periodicalUpdate('listSorties', {
      frequency: 120,
      onCreate: function() {
        WaitingMessage.cover($('listSorties'));
        Admissions.rememberSelection();
      }
    });
  });
</script>

<div style="display: none" id="area_prompt_modele">
  {{mb_include module=admissions template=inc_prompt_modele type=sortie}}
</div>

<table class="main">
  <tr>
    <td>
      <a href="#legend" onclick="Admissions.showLegend()" class="button search">Légende</a>
    </td>
    <td style="float: right">
      <form action="?" name="selType" method="get">
        <input type="hidden" name="date" value="{{$date}}" />
        <input type="hidden" name="selSortis" value="{{$selSortis}}" />
        <input type="hidden" name="order_col" value="{{$order_col}}" />
        <input type="hidden" name="order_way" value="{{$order_way}}" />
        <input type="hidden" name="filterFunction" value="{{$filterFunction}}" />
        <select name="period" onchange="reloadSorties();">
          <option value=""      {{if !$period          }}selected{{/if}}>&mdash; Toute la journée</option>
          <option value="matin" {{if $period == "matin"}}selected{{/if}}>Matin</option>
          <option value="soir"  {{if $period == "soir" }}selected{{/if}}>Soir</option>
        </select>
        {{mb_field object=$sejour field="_type_admission" emptyLabel="CSejour.all" onchange="reloadFullSorties();"}}
        <button type="button" onclick="selectServices('listSorties');" class="search">{{tr}}Services{{/tr}}</button>
        <select name="prat_id" onchange="reloadFullSorties();">
          <option value="">&mdash; Tous les praticiens</option>
          {{mb_include module=mediusers template=inc_options_mediuser list=$prats selected=$sejour->praticien_id}}
        </select>
      </form>
      <a href="#" onclick="printPlanning()" class="button print">Imprimer</a>
      <a href="#" onclick="Admissions.beforePrint(); Modal.open('area_prompt_modele')" class="button print">{{tr}}CCompteRendu-print_for_select{{/tr}}</a>

      {{mb_include module=hospi template=inc_send_prestations type=sortie}}
    </td>
  </tr>
  <tr>
    <td id="allSorties" style="width: 250px">
    </td>
    <td id="listSorties" style="width: 100%">
    </td>
  </tr>
</table>