{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=title   value=false}}
{{mb_default var=title_2 value=false}}

<button class="fas fa-chevron-left fa-pull-left" onclick="Facture.facturesByStateBack();">
  {{tr}}CFacture.others_exports.back{{/tr}}
</button>
<button class="fas fa-sync fa-pull-left" onclick="{{$reload_callback}}">
  {{tr}}CFacture.others_exports.reload{{/tr}}
</button>
{{if $title}}
  {{tr}}{{$title}}{{/tr}}
{{/if}}
{{if $title_2}}
  - {{tr}}{{$title_2}}{{/tr}}
{{/if}}
<button class="fas fa-download fa-pull-right" onclick="{{$csv_callback}}">
  {{tr}}Export{{/tr}}
</button>
