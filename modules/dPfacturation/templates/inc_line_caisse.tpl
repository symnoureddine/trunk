{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var="caisse" value=$_acte_caisse->_ref_caisse_maladie}}
{{if $facture->type_facture == "accident"}}
  {{assign var="coeff_caisse" value=$_acte_caisse->_ref_caisse_maladie->coeff_accident}}
{{else}}
  {{assign var="coeff_caisse" value=$_acte_caisse->_ref_caisse_maladie->coeff_maladie}}
{{/if}}
<tr>
  <td style="text-align:center;width:100px;">
    {{if $facture->_ref_last_sejour->_id}}
      <span onmouseover="ObjectTooltip.createEx(this, '{{$facture->_ref_last_sejour->_guid}}')">
    {{else}}
      <span onmouseover="ObjectTooltip.createEx(this, '{{$facture->_ref_last_consult->_guid}}')">
    {{/if}}
      {{$_acte_caisse->execution|date_format:$conf.date}}
    </span>
  </td>
  {{if $_acte_caisse->code}}
    <td class="acte-{{$_acte_caisse->_class}}">
        <span onmouseover="ObjectTooltip.createEx(this, '{{$_acte_caisse->_guid}}')">
          {{mb_value object=$_acte_caisse field="code"}}
        </span>
    </td>
  {{else}}
    <td></td>
  {{/if}}
  <td style="white-space: pre-line;" class="compact">{{$_acte_caisse->_ref_prestation_caisse->libelle}}</td>
  <td style="text-align:right;">
    {{$_acte_caisse->montant_base|string_format:"%0.2f"}} Pts
  </td>
  <td style="text-align:right;">{{mb_value object=$_acte_caisse field="quantite"}}</td>
  <td style="text-align:right;">{{$coeff_caisse}}</td>
  <td style="text-align:right;">{{$_acte_caisse->montant_base*$coeff_caisse*$_acte_caisse->quantite|string_format:"%0.2f"|currency}}</td>
</tr>