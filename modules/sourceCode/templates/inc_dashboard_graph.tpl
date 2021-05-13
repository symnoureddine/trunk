{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $graph->commitInfosList|@count}}
  <script>
    Main.add(function () {
      SourceCode.commitInfoList = {{$graph->commitInfosList|@json}};
      SourceCode.initDashboard();
    });
  </script>

  <tr>
    <td colspan="4">
      <div class="dc-data-count small-info"></div>
    </td>
  </tr>
  <tr>
    <td colspan="1">
      <div id="row-chart-types">
        <strong style="display:block; text-align: center;">{{tr}}sourceCode-legend-Type commit{{/tr}}</strong>
      </div>
    </td>
    <td colspan="1">
      <div id="pie-chart-types">
      </div>
    </td>
    <td colspan="1">
      <div id="row-chart-users">
        <strong style="display:block; text-align: center;">{{tr}}sourceCode-legend-User commit{{/tr}}</strong>
      </div>
    </td>
    <td colspan="1" rowspan="2">
      <div id="row-chart-branches">
        <strong style="display:block; text-align: center;">{{tr}}sourceCode-legend-Branch commit{{/tr}}</strong>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="3">
      <div id="line-chart-commits">
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="3">
      <div id="bar-chart-commits">
      </div>
    </td>
    <td colspan="1">
      <div id="row-chart-projects">
        <strong style="display:block; text-align: center;">{{tr}}sourceCode-legend-Project commit{{/tr}}</strong>
      </div>
    </td>
  </tr>
{{else}}
  <tr>
    <td class="error" colspan="4">
      <div class="small-error">
        {{tr}}common-No data{{/tr}}
      </div>
    </td>
  </tr>
{{/if}}