{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table id="autres_exports_selection" class="tbl">
  <thead>
    <tr>
      <th class="title narrow"></th>
      <th class="title">{{tr}}CFacture.others_exports{{/tr}}</th>
    </tr>
  </thead>
  {{foreach from=$others_exports item=_exports key=_facture_class}}
    <tr>
      <th class="category" colspan="2">{{tr}}{{$_facture_class}}{{/tr}}</th>
    </tr>
    {{foreach from=$_exports item=_state}}
      <tr>
        <td>
          <button class="fas fa-file-download notext" type="button"
                  onclick="Facture.printFacturesByState('{{$_state}}', '{{$_facture_class}}')">
            {{tr}}Export{{/tr}}
          </button>
        </td>
        <td>
          <strong>{{tr}}CFacture.factures_by_state.{{$_state}}{{/tr}}</strong> -
          {{tr}}CFacture.factures_by_state.{{$_state}}-desc{{/tr}}
        </td>
      </tr>
    {{/foreach}}
  {{/foreach}}
  <tr>
    <th class="category" colspan="2">{{tr}}General{{/tr}}</th>
  </tr>
  <tr>
    <td>
      <button class="fas fa-file-download notext" type="button" onclick="Facture.printFacturesTarmedCotation()">
        {{tr}}Export{{/tr}}
      </button>
    </td>
    <td>
      <strong>
        {{tr}}CFacture.factures_tarmed_cotation{{/tr}}
      </strong>
      {{tr}}CFacture.factures_tarmed_cotation-desc{{/tr}}
    </td>
  </tr>
  <tr>
    <td>
      <button class="fas fa-file-download notext" type="button" onclick="Facture.printCotations()">{{tr}}Export{{/tr}}</button>
    </td>
    <td>
      <strong>{{tr}}CFacture.cotations_consultation{{/tr}}</strong>
      {{tr}}CFacture.cotations_consultation-desc{{/tr}}
    </td>
  </tr>
</table>
<div id="autres_exports_container" style="display:none">
</div>