{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  graphs = {{$graphs|@json}};
  graphSizes = [
    {width: '400px', height: '250px', yaxisNoTicks: 5},
    {width: '700px', height: '500px', yaxisNoTicks: 10}
  ];

  yAxisTickFormatter = function(val) {
    return Flotr.engineeringNotation(val, 2, 1000);
  };

  drawDSGraphs = function(size) {
    var container, legend;
    size = size || graphSizes[0];
    $A(graphs).each(function(g, key) {
      container = $('datasource-graph-'+key);
      legend = $('datasource-legend-'+key);

      container.setStyle(size);
      g.options.y2axis.noTicks = size.yaxisNoTicks;
      g.options.yaxis.noTicks = size.yaxisNoTicks;
      g.options.yaxis.tickFormatter  = yAxisTickFormatter;
      g.options.y2axis.tickFormatter = yAxisTickFormatter;
      g.options.legend = {
        container:legend,
        noColumns: 3
      };
      g.options.mouse                = {
        track: true,
        position: "ne",
        relative: true,
        sensibility: 2,
        trackDecimals: 3,
        trackFormatter: function (obj) {
          return "DSN : " + obj.series.label + "<br />Valeur : " + obj.y + "<br />Date : " + g.datetime_by_index[obj.index];
        }
      };
      var f = Flotr.draw(container, g.series, g.options);

      {{if $groupmod==1}}
      f.overlay.setStyle({cursor: 'pointer'})
        .observe('click', function(m){return function(){$V(getForm('datasource-typevue').groupmod, m)}}(g.module));
      {{/if}}
    });
  }
</script>

<script>
  Main.add(function() {
    drawDSGraphs({{if $groupmod == 2}}graphSizes[1]{{/if}});
  });
</script>

{{foreach from=$graphs item=graph name=graphs}}
  <div style="float: left; margin: 1em;">
    <div id="datasource-graph-{{$smarty.foreach.graphs.index}}" style="width: 350px; height: 250px; display: inline-block;"></div>
    <div id="datasource-legend-{{$smarty.foreach.graphs.index}}" style="display: inline-block; vertical-align: top;"></div>
  </div>
{{/foreach}}

<!-- For styles purpose -->
<div style="clear: both;"></div>