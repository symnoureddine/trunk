{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<tr>
  <td style="text-align:center;width:100px;">
    {{if $facture->_ref_last_sejour->_id}}
      <span onmouseover="ObjectTooltip.createEx(this, '{{$facture->_ref_last_sejour->_guid}}')">
    {{else}}
      <span onmouseover="ObjectTooltip.createEx(this, '{{$facture->_ref_last_consult->_guid}}')">
    {{/if}}
      {{$_acte_tarmed->execution|date_format:$conf.date}}
    </span>
  </td>
  {{if $_acte_tarmed->code}}
    <td class="acte-{{$_acte_tarmed->_class}}">
      <span onmouseover="ObjectTooltip.createEx(this, '{{$_acte_tarmed->_guid}}')">
        {{mb_value object=$_acte_tarmed field="code"}}
      </span>
    </td>
  {{else}}
    <td></td>
  {{/if}}
  <td class="compact" style="white-space: pre-line;">
    {{if $_acte_tarmed->libelle}}
      {{$_acte_tarmed->libelle}}
    {{else}}
      {{$_acte_tarmed->_ref_tarmed->libelle}}
    {{/if}}
  </td>
  <td style="text-align:right;">
    {{$_acte_tarmed->montant_base|string_format:"%0.2f"}} {{tr}}CFactureItem.pts{{/tr}}
  </td>
  <td style="text-align:right;">{{mb_value object=$_acte_tarmed field="quantite"}}</td>
  <td style="text-align:right;">{{$facture->_coeff}}</td>
  <td style="text-align:right;">{{$_acte_tarmed->montant_base*$facture->_coeff*$_acte_tarmed->quantite|string_format:"%0.2f"|currency}}</td>
</tr>