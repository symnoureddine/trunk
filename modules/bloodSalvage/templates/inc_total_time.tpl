{{*
 * @package Mediboard\BloodSalvage
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="form">
  <tr>
    <td style="text-align:center;">
      <b>Durée totale RSPO : </b>
      {{$totaltime|date_format:$conf.time}}
    </td>
  </tr>
</table>
{{if $totaltime > "05:00:00" && $totaltime < "06:00:00" }}
  <div class="small-warning" style="text-align:center;">
    Limite légale de 6 heures bientôt atteinte ! <br />
    <b>Temps restant : {{$timeleft|date_format:$conf.longtime}}</b>
  </div>
{{/if}}
{{if $totaltime > "06:00:00"}}
  <div class="small-error" style="text-align:center;">
    Limite légale de 6 heures dépassée !
  </div>
{{/if}}
