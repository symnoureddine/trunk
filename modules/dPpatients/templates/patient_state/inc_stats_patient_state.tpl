{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  drawLoadGraph = function () {
    var oDatum = {{$graph.datum|@json}};
    var oOptions = {{$graph.options|@json}};

    var oPh = jQuery("#graph_occupation");
    oPh.bind('plothover', plotHover);
    var plot = jQuery.plot(oPh, oDatum, oOptions);

    var oDatum2 = {{$graph2.datum|@json}};
    var oOptions2 = {{$graph2.options|@json}};

    var oPh2 = jQuery("#graph_occupation2");
    oPh2.bind('plothover', plotHover);
    oPh2.bind('plotclick', plotClick);
    var plot2 = jQuery.plot(oPh2, oDatum2, oOptions2);
  };

  plotHover = function (event, pos, item) {
    if (item) {
      jQuery("#flot-tooltip").remove();
      var abscisse = parseInt(pos.x1) | 0;

      content = item.series.label + "<br /><strong>" + item.series.data[abscisse][1] + " " + item.series.unit + "</strong>";

      if (item.series.bars.show) {
        content += "<br />" + item.series.data[abscisse].day;
      }

      $$("body")[0].insert(DOM.div({className: "tooltip", id: "flot-tooltip"}, content).setStyle({
        top:  pos.pageY + "px",
        left: parseInt(pos.pageX) + "px"
      }));
    } else {
      jQuery("#flot-tooltip").remove();
    }
  };

  plotClick = function (event, pos, item) {
    if (item) {
      var x = parseInt(pos.x1);
      var ids = item.series.data[x].ids;

      if (ids) {
        showMergedPatients(item.series.data[x].day, ids);
      }
    }
  };

  showMergedPatients = function (date, ids) {
    var url = new Url('dPpatients', 'ajax_show_merged_patients');
    url.addParam('date', date);
    url.addParam('ids', ids);

    url.requestModal(800, 600);
  };

  Main.add(function () {
    drawLoadGraph();

    var form = getForm('filter_graph_bar_patient_state');
    form.elements._number_day.addSpinner({min: 0, max: 31});
  });
</script>

<div class="small-info">
  Il y a <strong>{{$graph.count}}</strong> patients dont le statut de l'identité est renseigné sur les
  <strong>{{$total_patient}}</strong> patients de l'instance.
</div>

<table class="layout">
  <tr>
    <td>
      <p style="text-align: center">
        <strong>{{tr}}{{$graph.title}}{{/tr}} &bull; {{$graph.count}} {{$graph.unit}}</strong>
      </p>

      <div style="width: 500px; height: 500px;" id="graph_occupation"></div>
    </td>

    <td>
      <p style="text-align: center">
        <strong>{{tr}}{{$graph2.title}}{{/tr}} &bull; {{$graph2.count}} {{$graph2.unit}}</strong>
      </p>

      <div style="width: 500px; height: 500px;" id="graph_occupation2"></div>
      {{if $_merge_patient}}
        <div class="small-info">
          {{tr}}CPatientState-msg-Click on bars in order to show merge details.{{/tr}}
        </div>
      {{/if}}
    </td>
  </tr>

  <tr>
    <td style="text-align: center" colspan="2">
      <hr />
      <form name="filter_graph_bar_patient_state" method="post" onsubmit="return PatientState.stats_filter(this)">
        <table class="main form">
          <tr>
            <th>{{mb_label class=CPatientState field=_date_end}}</th>

            <td>
              {{mb_field class=CpatientState field=_date_end register=true form=filter_graph_bar_patient_state value=$_date_end}}
            </td>
          </tr>

          <tr>
            <th>{{mb_label class=CPatientState field=_number_day}}</th>

            <td>{{mb_field class=CpatientState field=_number_day value=$_number_day}}</td>
          </tr>

          <tr>
            <th>{{mb_label class=CPatientState field=_merge_patient}}</th>

            <td>{{mb_field class=CpatientState field=_merge_patient value=$_merge_patient}}</td>
          </tr>

          <tr>
            <td colspan="2" class="button">
              <button class="search" type="submit">{{tr}}Filter{{/tr}}</button>
              <button class="download" type="button" onclick="PatientState.downloadCSV()">{{tr}}Download{{/tr}}</button>
            </td>
          </tr>
        </table>
      </form>
    </td>
  </tr>
</table>