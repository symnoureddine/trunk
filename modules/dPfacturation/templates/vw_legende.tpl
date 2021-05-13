{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="tbl">
  <tr>
    <th colspan="2">{{tr}}CFacture{{/tr}}</th>
  </tr>
  <tr>
    <td style="background-color:#ffcd75;width:40px;"></td>
    <td class="text">{{tr}}CFactureCabinet-facture.no-cote|f{{/tr}}</td>
  </tr>
  {{if !"dPfacturation $classe use_auto_cloture"|gconf}}
    <tr>
      <td style="background-color:#fcc;width:40px;"></td>
      <td class="text">{{tr}}CFactureCabinet-facture.no-cloture|f{{/tr}}</td>
    </tr>
  {{/if}}
  <tr>
    <td class="item_superior"></td>
    <td class="text">{{tr}}CFactureCabinet-facture.rejete|f{{/tr}}</td>
  </tr>
  <tr>
    <td style="background-color:#cfc;width:40px;"></td>
    <td class="text">{{tr}}CFactureCabinet-facture.regle|f{{/tr}}</td>
  </tr>
  <tr>
    <td class="hatching"></td>
    <td class="text">{{tr}}CFactureCabinet-facture.extourne|f{{/tr}}</td>
  </tr>
</table>