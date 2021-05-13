{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=facturation script=comptabilite ajax=true}}

<div id="form_upload">
  <h2>{{tr}}compta-import_v11{{/tr}} {{tr}}{{$facture_class}}{{/tr}}</h2>
  <div class="big-info">{{tr}}Gestion.import_v11{{/tr}}</div>

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

{{if $results|@count}}
  <table class="tbl">
    <tr>
      <th class="title" colspan="15">
        {{$results|@count}} {{tr}}compta-reglement_find{{/tr}}
        <button id="button_print" class="print" type="button" style="float: right;" onclick="Comptabilite.v11.impression();">
          {{tr}}Print{{/tr}}
        </button>
        <button type="button" class="download not-printable" style="float:right;" data-csv="{{$result_csv}}"
                onclick="Comptabilite.v11.export(this)">
          {{tr}}Export{{/tr}}
        </button>
      </th>
    </tr>
    <tr>
      <th>{{tr}}compta-transmission{{/tr}}</th>
      <th>{{tr}}compta-num_adherent{{/tr}}</th>
      <th>{{tr}}CFacture-type_sejour{{/tr}}</th>
      <th>{{tr}}CFacture{{/tr}}</th>
      <th>{{tr}}CDebiteur{{/tr}}</th>
      <th>{{tr}}CFacture-montant{{/tr}}</th>
      <th>{{tr}}compta-reference{{/tr}}</th>
      <th>{{tr}}compta-date_depot{{/tr}}</th>
      <th>{{tr}}compta-date_traitement{{/tr}}</th>
      <th>{{tr}}compta-date_valeur{{/tr}}</th>
      <th>{{tr}}compta-rejet{{/tr}}</th>
      <th>R</th>
      <th>{{tr}}compta-microfilm{{/tr}}</th>
      <th>{{tr}}compta-erreur{{/tr}}</th>
    </tr>
    {{foreach from=$results item=_reglement}}
      {{assign var=facture value=$_reglement.facture}}
      <tr>
        <td class="text">{{$_reglement.genre}}</td>
        <td class="text">{{$_reglement.num_client}}</td>
        <td class="text">
          {{if $facture->_class == "CFactureEtablissement"}}
            {{$facture->_ref_last_sejour->_id}}
          {{/if}}
        </td>
        <td class="text">{{$facture}}</td>
        <td class="text">{{$facture->_ref_patient}}</td>
        <td class="text" style="text-align: right;">{{$_reglement.montant}}</td>
        <td class="text">{{$_reglement.reference}}</td>
        <td class="text">{{$_reglement.date_depot|date_format:$conf.date}}</td>
        <td class="text">{{$_reglement.date_traitement|date_format:$conf.date}}</td>
        <td class="text">{{$_reglement.date_inscription|date_format:$conf.date}}</td>
        <td class="text">{{$_reglement.code_rejet}}</td>
        <td class="text">{{$facture->_ref_relances|@count}}</td>
        <td class="text">{{$_reglement.num_microfilm}}</td>
        <td class="text {{if $_reglement.errors|@count}}error{{elseif $_reglement.warning|@count}}warning{{else}}ok{{/if}} compact">
          {{foreach from=$_reglement.errors item=_error}}
            <div>{{$_error}}</div>
          {{/foreach}}
          {{foreach from=$_reglement.warning item=_error}}
            <div>{{$_error}}</div>
          {{/foreach}}
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
      <th>
        {{tr}}CFacture-montant{{/tr}}
        <button type="button" class="download not-printable notext" style="float:right;" data-csv="{{$totaux_csv}}"
                onclick="Comptabilite.v11.export(this)">
          {{tr}}Export{{/tr}}
        </button>
      </th>
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
      <td>{{tr}}compta-total_ptt{{/tr}}:</td>
      <td style="text-align: center;">{{$totaux.total.count}}</td>
      <td style="text-align: right;">{{$totaux.total.total|string_format:"%0.2f"}}</td>
    </tr>
  </table>
{{/if}}
