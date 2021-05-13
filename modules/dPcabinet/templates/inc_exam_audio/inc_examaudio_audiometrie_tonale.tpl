{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=legend_container value=true}}

<script>
  var frequences = {
    0: "125",
    1: "250",
    2: "500",
    3: "1000",
    4: "2000",
    5: "3000",
    6: "4000",
    7: "6000",
    8: "8000",
    9: "16000"
  };

  Main.add(function () {
    var data = {{$graph->series|@json}};
    {{if $old_consultation_id}}
      data_old = {{$graph_old->series|@json}};
      data_old.forEach(function (serie) {
        switch (serie.label){
          case "Conduction ar�rienne" :
            serie.color = "#B1D4FF";
            break;
          case "Conduction osseuse" :
            serie.color = "#FFB9B9";
            break;
          case "Stap�dien controlar�tal" :
            serie.color = "#D3D3D3";
            break;
          case "Stap�dien ipsilar�tal" :
            serie.color = "#D0A775";
            break;
          case "Pas de r�ponse" :
            serie.color = "#a1dba1";
            break;
        }
        serie.label = serie.label.concat(" - ", '{{$old_consultation->_date|date_format:$conf.longdate}}');
      });
      data = data_old.concat(data);
    {{/if}}

    var options = {{$graph->options|@json}};
    options.yaxis.tickFormatter = function (val) {
      return val + "dB";
    };
    {{if $legend_container}}
    options.legend.container = "#{{$legend_container}}";
    {{else}}
    options.legend.show = false;
    {{/if}}
    options.hooks = {
      processRawData: [function (plot, series, data, datapoints) {
        var symbol = series.points.symbol;
        switch (symbol) {
          case "circle":
          case "cross":
          case "triangle":
            break;
          case "IL":
          case "CL":
            series.points.symbol = function (ctx, x, y, radius, shadow) {
              ctx.font = "6px sans-serif bold";
              ctx.lineWidth = 0.5;
              ctx.fillStyle = "#eee";
              ctx.beginPath();
              ctx.arc(x, y, 6, 0, 2 * Math.PI);
              ctx.closePath();
              ctx.fill();
              ctx.stroke();
              //Ancienne consultation
              if (series.label.includes("-")) {
                ctx.fillStyle = "#b3acac";
              } else {
                ctx.fillStyle = "black";
              }
              ctx.fillText(symbol, x - ctx.measureText(symbol).width / 2, y + 3.5);
            };
            break;

          default:
            series.points.symbol = "circle";
            break;
        }
      }]
    };
    var ph = jQuery("#{{$graph->getId()}}");
    ph.bind("plotclick", function (event, pos, item) {
      if (item && $V(getForm("editFrm")._conduction) == item.series.type && !item.series.label.includes("-")) {
        //Ouverture de modale de modification
        ExamAudio.changeTonalValue('{{$graph->side}}', item.series.type, Math.round(pos.x), null, {{if $old_consultation_id}}{{$old_exam_audio->_id}}{{/if}});
      } else {
        //Cr�ation de point (modifie ceux de la m�me absisse)
        ExamAudio.changeTonalValue('{{$graph->side}}', null, Math.round(pos.x), -pos.y, {{if $old_consultation_id}}{{$old_exam_audio->_id}}{{/if}});
      }
    });
    ph.bind("plothover", function (event, pos, item) {
      jQuery("#flot-tooltip").remove();
      if (item && $V(getForm("editFrm")._conduction) == item.series.type && !item.series.label.includes("-")) {
        var content = "Modifier la valeur #{db}dB pour <br/> <strong>#{label}</strong> � #{frequence}".interpolate({
          db:        -item.datapoint[1],
          label:     item.series.label,
          frequence: frequences[item.datapoint[0]] + "Hz"
        });
        $$("body")[0].insert(DOM.div({className: "tooltip", id: "flot-tooltip"}, content).setStyle({
          top:  pos.pageY + 5 + "px",
          left: pos.pageX + 5 + "px"
        }));
      }
    });
    var plot = jQuery.plot(ph, data, options);
    ph[0].store("plot", plot);
  });
</script>

<strong>{{$graph->options.title}}</strong>
<br/>
<div id="{{$graph->getId()}}" style="width: 325px; height: 250px; display: inline-block;"></div>
