/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * JS function sourceCode
 */
SourceCode =
  window.SourceCode || {
    commitInfoList: {},
    
    initDashboard: function () {
      require(['dc'], function (dc) {
        
        window.dc = dc;
  
        var dateFormat = d3.time.format('%d/%m/%Y');
        
        SourceCode.commitInfoList.forEach(
          function (d) {
            let currDate = new Date(d.authored_date);
            d.date = d3.time.day(currDate);
            d.week = d3.time.week(currDate);
            d.second = d3.time.second(currDate);
          }
        );

        var cf  = crossfilter(SourceCode.commitInfoList);
        var all = cf.groupAll();
  
        /* Dimensions and Groups */
        
        /* Types (bug, fnc, ref, ...) */
        var typesDim   = cf.dimension(function(d){ return d.type;}, true);
        var typesGroup = typesDim.group();
  
        /* Users */
        var usersDim   = cf.dimension(function(d){ return d.user_name;}, true);
        var usersGroup = usersDim.group();
  
        /* Branches */
        var branchesDim   = cf.dimension(function(d){ return d.branch_name;}, true);
        var branchesGroup = branchesDim.group();
  
        /* Projets */
        var projectsDim   = cf.dimension(function(d){ return d.project_name;}, true);
        var projectsGroup = projectsDim.group();
  
        /* Dates */
        var dateDim = cf.dimension(
          function (d) {
            return d.date;
          }
        );
        var dateGroup = dateDim.group();
        var dateWeekDim = cf.dimension(
          function (d) {
            return d.week;
          }
        );
        var dateWeekGroup = dateWeekDim.group();
  
        /* Get container width */
        var containerWidth = $("sourceCode-dashboard").offsetWidth - 10;

        /* Set the date boundaries */
        var minDate = dateWeekDim.bottom(1)[0].week;
        var maxDate = dateDim.top(1)[0].date;
        
        SourceCode.typesRowChart         = dc.rowChart("#row-chart-types");
        SourceCode.typesPieChart         = dc.pieChart("#pie-chart-types");
        SourceCode.usersRowChart         = dc.rowChart("#row-chart-users");
        SourceCode.branchesRowChart      = dc.rowChart("#row-chart-branches");
        SourceCode.projectsRowChart      = dc.rowChart("#row-chart-projects");
        SourceCode.commitsTrendLineChart = dc.lineChart("#line-chart-commits");
        SourceCode.commitsBarChart       = dc.barChart("#bar-chart-commits");
        SourceCode.commitDataCount       = dc.dataCount(".dc-data-count");
  
        SourceCode.commitDataCount
          .dimension(cf)
          .group(all)
          .html({
            some: "<strong>%filter-count</strong> sélectionnés sur <strong>%total-count</strong> enregistrements" +
              " &verbar; <a href='#1' onclick='SourceCode.clearFilter();'>Réinitialiser les filtres</a>",
            all:  $T("sourceCode-msg-All records selected") + " (<strong>%total-count</strong> enregistrements)"
          });
        
        SourceCode.typesRowChart
          .renderLabel(true)
          .height(300)
          .width(containerWidth * 0.25)
          .dimension(typesDim)
          .group(typesGroup)
          .cap(7)
          .ordering(function(d){return -d.value;})
          .xAxis().ticks(3);

        SourceCode.typesPieChart
          .height(300)
          .width(containerWidth * 0.25)
          .dimension(typesDim)
          .group(typesGroup)
          .innerRadius(50)
          .label(function(d) {
            return (all.value() > 0 ? Math.round(d.value / all.value() * 100) : '0') + '%';
          })
          .title(function(d) {
            return d.key + ': '+ d.value + ' (' + (all.value() > 0 ? (d.value / all.value() * 100).toFixed(2) : '0') + '%)';
          });
  
        SourceCode.usersRowChart
          .renderLabel(true)
          .height(300)
          .width(containerWidth * 0.25)
          .dimension(usersDim)
          .group(usersGroup)
          .cap(30)
          .ordering(function(d){return -d.value;})
          .xAxis().ticks(3);
        
        SourceCode.commitsTrendLineChart
          .width(containerWidth * 0.75).height(100)
          .dimension(dateDim)
          .group(dateGroup)
          .renderArea(true)
          .interpolate('cardinal')
          .filterPrinter(function (filters) {
            var filter = filters[0], s = 'Filtre du ';
            s += dateFormat(filter[0]) + ' au ' + dateFormat(filter[1]) + ' |';
            return s;
          })
          .x(d3.time.scale().domain([minDate, maxDate]))
          .elasticY(true)
          .yAxisLabel("") // In order to align with the previous graph
          .yAxis().ticks(2);
  
        SourceCode.commitsBarChart
          .width(containerWidth * 0.75).height(250)
          .brushOn(false)
          .dimension(dateDim)
          .group(dateGroup)
          .centerBar(true)
          .transitionDuration(0)
          .renderVerticalGridLines(true)
          .renderHorizontalGridLines(true)
          .x(d3.time.scale().domain([minDate, maxDate]))
          .rangeChart(SourceCode.commitsTrendLineChart)
          .elasticY(true)
          .title(function (d) {
            return "Date : " + dateFormat(d.key) + "\nCommits : " + d.value;
          })
          .yAxisLabel($T("sourceCode-legend-Commit Count"))
          .xUnits(d3.time.days);
  
        SourceCode.branchesRowChart
          .width(containerWidth * 0.25).height(500)
          .dimension(branchesDim)
          .group(branchesGroup)
          .renderTitleLabel(true)
          .colors(d3.scale.category20b())
          .label(function (d) {
            return d.key;
          })
          .title(function (d) {
            return d.value;
          });
  
        SourceCode.projectsRowChart
          .width(containerWidth * 0.25).height(200)
          .dimension(projectsDim)
          .group(projectsGroup)
          .renderTitleLabel(true)
          .colors(d3.scale.category20c())
          .label(function (d) {
            return d.key;
          })
          .title(function (d) {
            return d.value;
          });

        dc.renderAll();
      });
    },
    
    clearFilter: function (graph) {
      graph ? graph.filterAll() : dc.filterAll();
      dc.redrawAll();
    },
    
  };