{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=facturation script=comptabilite ajax=true}}

<div id="form_upload">
  <h2>{{tr}}compta-importCamt054{{/tr}} {{tr}}{{$facture_class}}{{/tr}}</h2>
  <div class="big-info">{{tr}}Gestion.importCamt054{{/tr}}</div>

  <form method="post" action="?m={{$m}}&{{$actionType}}={{$action}}&dialog=1" name="import" enctype="multipart/form-data">
    <input type="hidden" name="m" value="{{$m}}" />
    <input type="hidden" name="{{$actionType}}" value="{{$action}}" />
    <input type="hidden" name="MAX_FILE_SIZE" value="4096000" />
    <input type="hidden" name="facture_class" value="{{$facture_class}}" />
    <input type="file" name="import" />
    <input type="checkbox" name="dryrun" value="1" checked="checked" />
    <label for="dryrun">{{tr}}DryRun{{/tr}}</label>
    <button class="submit">{{tr}}Save{{/tr}}</button>
  </form>
</div>

{{if $format_invalid}}
  <div class="small-error">{{tr}}CFacture.camt054.format_invalid{{/tr}}</div>
{{/if}}
{{if $results|@count}}
  <table class="tbl">
    <tr>
      <th class="title" colspan="15">
        {{$results|@count}} {{tr}}compta-reglement_find{{/tr}}
        <button id="button_print" class="print" type="button" style="float: right;" onclick="Comptabilite.camt054.impression();">
          {{tr}}Print{{/tr}}
        </button>
      </th>
    </tr>
    <tr>
      <th>{{tr}}{{$facture_class}}{{/tr}}</th>
      <th>{{tr}}compta-reference{{/tr}}</th>
      <th>{{tr}}CFacture-montant{{/tr}}</th>
      <th>{{tr}}compta-date_depot{{/tr}}</th>
      <th>{{tr}}compta-erreur{{/tr}}</th>
    </tr>
    {{foreach from=$results item=_result}}
      {{assign var=_facture value=$_result.facture}}
      <tr>
        <td class="text">
          {{if $_facture->_id}}
            <span onmouseover="ObjectTooltip.createEx(this, '{{$_facture->_guid}}')">
              {{$_facture->_view}}
            </span>
          {{/if}}
        </td>
        <td class="text">{{$_result.reference}}</td>
        <td class="text">{{$_result.montant}} {{$conf.currency_symbol|html_entity_decode}}</td>
        <td class="text">{{$_result.date_depot|date_format:$conf.date}}</td>
        <td class="textc compact
          {{if isset($_result.errors|smarty:nodefaults)}}
            error
          {{elseif isset($_result.warning|smarty:nodefaults)}}
            warning
          {{else}}
            ok
          {{/if}}">
          {{if isset($_result.errors|smarty:nodefaults)}}
            {{foreach from=$_result.errors item=_error}}
              <div>{{$_error}}</div>
            {{/foreach}}
          {{/if}}
          {{if isset($_result.warning|smarty:nodefaults)}}
            {{foreach from=$_result.warning item=_warning}}
              <div>{{$_warning}}</div>
            {{/foreach}}
          {{/if}}
        </td>
      </tr>
    {{/foreach}}
  </table>
  <br/>
  <br/>
  <table class="form tbl" style="width: 500px;">
    <tr>
      <th>{{tr}}Date{{/tr}}</th>
      <th>{{tr}}compta-recordings{{/tr}}</th>
      <th>{{tr}}CFacture-montant{{/tr}} ({{$conf.currency_symbol|html_entity_decode}})</th>
    </tr>
    {{foreach from=$totaux.impute.dates item=ligne key=date}}
      <tr>
        <td>{{$date|date_format:$conf.date}}</td>
        <td style="text-align: center;">{{$ligne.count}}</td>
        <td style="text-align: right;">{{$ligne.total|string_format:"%0.2f"}}</td>
      </tr>
    {{/foreach}}
    <tr>
      <td colspan="3"><br/></td>
    </tr>
    <tr>
      <td>{{tr}}compta-total_impute{{/tr}}:</td>
      <td style="text-align: center;">{{$totaux.impute.count}}</td>
      <td style="text-align: right;">{{$totaux.impute.total|string_format:"%0.2f"}}</td>
    </tr>
    <tr>
      <td>{{tr}}compta-total_rejet{{/tr}}:</td>
      <td style="text-align: center;">{{$totaux.rejete.count}}</td>
      <td style="text-align: right;">{{$totaux.rejete.total|string_format:"%0.2f"}}</td>
    </tr>
    <tr>
      <td>{{tr}}Total{{/tr}}:</td>
      <td style="text-align: center;">{{$totaux.total.count}}</td>
      <td style="text-align: right;">{{$totaux.total.total|string_format:"%0.2f"}}</td>
    </tr>
  </table>
{{/if}}
