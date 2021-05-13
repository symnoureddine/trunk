{{*
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="main tbl">
  <tr>
    <td>
      <form name="reset-import-stats" method="get" onsubmit="return onSubmitFormAjax(this)">
        <input type="hidden" name="m" value="openData"/>
        <input type="hidden" name="a" value="ajax_reset_stats"/>

        <button class="cancel" type="submit">
          {{tr}}CMedecinImport-reset-stats{{/tr}}
        </button>
      </form>
    </td>
    <th>
      {{tr}}CMedecinImport-stats-total{{/tr}}
    </th>
    <th>
      {{tr}}CMedecinImport-stats-actual{{/tr}}
    </th>
  </tr>

  <tr>
    <th style="text-align: left;">{{tr}}CMedecinImport-total_time{{/tr}}</th>
    <td style="text-align: right;">{{$total_time}}</td>
    <td style="text-align: right;">{{if $time}}{{$time|number_format:4:',':' '}} seconde(s){{/if}}</td>
  </tr>

  <tr>
    <th style="text-align: left;">{{tr}}CMedecinImport-nb_news{{/tr}}</th>
    <td style="text-align: right;">{{$total_stats.nb_news}}</td>
    <td style="text-align: right;">{{$nb_news}}</td>
  </tr>

  <tr>
    <th style="text-align: left;">{{tr}}CMedecinImport-nb_exists{{/tr}}</th>
    <td style="text-align: right;">{{$total_stats.nb_exists}}</td>
    <td style="text-align: right;">{{$nb_exists}}</td>
  </tr>

  <tr>

    <th style="text-align: right;">{{tr}}CMedecinImport-nb_exists_used{{/tr}}</th>
    <td style="text-align: right;">{{$total_stats.nb_exists_used}}</td>
    <td style="text-align: right;">{{$nb_used}}</td>
  </tr>

  <tr>

    <th style="text-align: right;">{{tr}}CMedecinImport-nb_exists_unused{{/tr}}</th>
    <td style="text-align: right;">{{$total_stats.nb_exists_unused}}</td>
    <td style="text-align: right;">{{$nb_unused}}</td>
  </tr>

  <tr>
    <th style="text-align: left;">{{tr}}CMedecinImport-nb_exists_conflict{{/tr}}</th>
    <td style="text-align: right;">{{$total_stats.nb_exists_conflict}}</td>
    <td style="text-align: right;">{{$nb_conflicts}}</td>
  </tr>

  <tr>
    <th style="text-align: left;">{{tr}}CMedecinImport-nb_tel_error{{/tr}}</th>
    <td style="text-align: right;">{{$total_stats.nb_tel_error}}</td>
    <td style="text-align: right;">{{$nb_tel_error}}</td>
  </tr>

  <tr>
    <th style="text-align: left;">{{tr}}CMedecinImport-nb_rpps_ignore{{/tr}}</th>
    <td style="text-align: right;">{{$total_stats.nb_rpps_ignored}}</td>
    <td style="text-align: right;">{{$nb_rpps}}</td>
  </tr>
</table>
