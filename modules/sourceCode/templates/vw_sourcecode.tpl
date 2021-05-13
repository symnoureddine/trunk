{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=sourceCode script=sourcecode}}

<script>
  require.config({
    paths: {
      d3: '/lib/d3-3.5.16/d3.min',
      dc: '/lib/dc/dc.min',
      crossfilter: '/lib/crossfilter/crossfilter.min'
    },
    shim: {
      'crossfilter': {
        deps:    [],
        exports: 'crossfilter'
      }
    }
  });

  Main.add(function () {
    var form = getForm("filterGraph");
    Calendar.regField(form.start_date);
    Calendar.regField(form.end_date);
  });
</script>

<form name="filterGraph" action="?m={{$m}}" method="get" onsubmit="return onSubmitFormAjax(this, null, 'sourceCode-dashboard');">
  <input type="hidden" name="m" value="{{$m}}" />
  <input type="hidden" name="a" value="ajax_update_dashboard" />
  <table class="main form">
    <tr>
      <th class="narrow">{{tr}}common-Date{{/tr}}</th>
      <td>
        {{mb_field object=$graph field='start_date' hidden=true value=$graph->start_date}}
        &raquo;
        {{mb_field object=$graph field='end_date' hidden=true value=$graph->end_date}}
        <button type="submit" class="search">{{tr}}Filter{{/tr}}</button>
      </td>
    </tr>
  </table>
  <table class="main" id="sourceCode-dashboard">
      {{mb_include module=sourceCode template=inc_dashboard_graph}}
  </table>
</form>