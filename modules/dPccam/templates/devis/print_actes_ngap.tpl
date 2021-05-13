{{*
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=montant_base value=0}}
{{assign var=depassement value=0}}
{{assign var=total value=0}}

<table class="tbl" style="font: inherit;">
  <tr>
    <th>Code</th>
    <th>Coefficient</th>
    <th>Montant base</th>
    <th>Dépassement</th>
    <th>Total</th>
  </tr>
  {{foreach from=$devis->_ref_actes_ngap item=_act}}
    <tr>
      <td>
        {{if $_act->quantite}}{{mb_value object=$_act field=quantite}} x {{/if}}{{$_act->code_acte}}
      </td>
      <td>
        {{mb_value object=$_act field=coefficient}}
      </td>
      <td style="text-align: right;">
        {{mb_value object=$_act field=montant_base}}
      </td>
      <td style="text-align: right;">
        {{mb_value object=$_act field=montant_depassement}}
      </td>
      <td style="text-align: right;">
        {{mb_value object=$_act field=_tarif}}
      </td>
    </tr>
    {{math assign=montant_base equation="x+y" x=$montant_base y=$_act->montant_base}}
    {{math assign=depassement equation="x+y" x=$depassement y=$_act->montant_depassement}}
    {{math assign=total equation="x+y" x=$total y=$_act->_tarif}}
  {{/foreach}}
  <tr>
    <th>Total</th>
    <th></th>
    <th style="text-align: right;">{{$montant_base|currency}}</th>
    <th style="text-align: right;">{{$depassement|currency}}</th>
    <th style="text-align: right;">{{$total|currency}}</th>
  </tr>
</table>