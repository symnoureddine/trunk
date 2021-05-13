{{*
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="main tbl">
  <tr>
    <th class="title" colspan="2">
      {{if $devis->event_type == 'CConsultation'}}
        {{assign var=event_type value='consultation'}}
      {{else}}
        {{assign var=event_type value='intervention'}}
      {{/if}}
      Devis pour une {{$event_type}} le {{mb_value object=$devis field=date}}
    </th>
  </tr>
  <tr>
    <th colspan="2" class="category">
      {{$devis->libelle}}
    </th>
  </tr>
  <tr>
    <td class="halfpane" id="infosPraticien">
      <fieldset>
        {{assign var=praticien value=$devis->_ref_praticien}}
        <legend>Praticien</legend>
        <table class="tbl">
          <tr>
            <td style="text-align: left;">Nom :</td>
            <td style="text-align: left;">{{mb_value object=$praticien field=_user_last_name}} {{mb_value object=$praticien field=_user_first_name}}</td>
          </tr>
          {{if $praticien->adeli || $praticien->rpps}}
            {{mb_ternary var=idnat test=$praticien->adeli value=$praticien->adeli other=$praticien->rpps}}
            <tr>
              <td style="text-align: left;">N° Identifiant :</td>
              <td style="text-align: left;">{{$idnat}}</td>
            </tr>
          {{/if}}
          {{if $praticien->_ref_function}}
            {{assign var=function value=$praticien->_ref_function}}
            {{if $function->adresse && $function->ville && $function->cp}}
              <tr>
                <td style="text-align: left;">Adresse :</td>
                <td style="text-align: left;">{{$function->adresse}} {{$function->cp}} {{$function->ville}}</td>
              </tr>
            {{/if}}
            {{if $function->tel}}
              <tr>
                <td style="text-align: left;">Téléphone :</td>
                <td style="text-align: left;">{{$function->tel}}</td>
              </tr>
            {{/if}}
          {{/if}}
        </table>
      </fieldset>
    </td>
    <td class="halfPane" id="infosPatient">
      <fieldset>
        {{assign var=patient value=$devis->_ref_patient}}
        <legend>Patient</legend>
        <table class="tbl">
          <tr>
            <td style="text-align: left;">Nom :</td>
            <td style="text-align: left;">{{mb_value object=$patient field=nom}} {{mb_value object=$patient field=prenom}}</td>
          </tr>
          <tr>
            <td style="text-align: left;">Adresse :</td>
            <td style="text-align: left;">{{$patient->adresse}} {{$patient->cp}} {{$patient->ville}}</td>
          </tr>
          <tr>
            <td style="text-align: left;">Téléphone :</td>
            <td style="text-align: left;">{{$patient->tel}}</td>
          </tr>
        </table>
      </fieldset>
    </td>
  </tr>
  <tr>
    <td colspan="2" id="infosActes">
      <table class="tbl">
        {{if $devis->_ref_actes_ccam|@count != 0}}
          <tr>
            <th colspan="7" class="title">Actes CCAM</th>
          </tr>
          <tr>
            <th>{{tr}}CActeCCAM-code_acte{{/tr}}</th>
            <th>{{tr}}CActeCCAM-code_activite{{/tr}}</th>
            <th>{{tr}}CActeCCAM-modificateurs{{/tr}}</th>
            <th>{{tr}}CActeCCAM-montant_base{{/tr}}</th>
            <th>{{tr}}CActeCCAM-montant_depassement{{/tr}}</th>
            <th></th>
            <th>Total</th>
          </tr>
          {{foreach from=$devis->_ref_actes_ccam item=_acte}}
            <tr>
              <td style="text-align: center;">{{mb_value object=$_acte field=code_acte}}</td>
              <td style="text-align: center;">{{mb_value object=$_acte field=code_activite}} - {{mb_value object=$_acte field=code_phase}}</td>
              <td style="text-align: center;">{{mb_value object=$_acte field=modificateurs}}</td>
              <td style="text-align: right;">{{mb_value object=$_acte field=montant_base}}</td>
              <td style="text-align: right;">{{mb_value object=$_acte field=montant_depassement}}</td>
              <td style="text-align: right;">-</td>
              <td style="text-align: right;">{{mb_value object=$_acte field=_tarif}}</td>
            </tr>
          {{/foreach}}
        {{/if}}
        {{if $devis->_ref_actes_ngap|@count != 0}}
          <tr>
            <th colspan="7" class="title">Actes NGAP</th>
          </tr>
          <tr>
            <th>{{tr}}CActeNGAP-quantite{{/tr}}</th>
            <th>{{tr}}CActeNGAP-code{{/tr}}</th>
            <th>{{tr}}CActeNGAP-coefficient{{/tr}}</th>
            <th>{{tr}}CActeNGAP-montant_base{{/tr}}</th>
            <th>{{tr}}CActeNGAP-montant_depassement{{/tr}}</th>
            <th></th>
            <th>Total</th>
          </tr>
          {{foreach from=$devis->_ref_actes_ngap item=_acte}}
            <tr>
              <td style="text-align: center;">{{mb_value object=$_acte field=quantite}}</td>
              <td style="text-align: center;">{{mb_value object=$_acte field=code}}</td>
              <td style="text-align: center;">{{mb_value object=$_acte field=coefficient}}</td>
              <td style="text-align: right;">{{mb_value object=$_acte field=montant_base}}</td>
              <td style="text-align: right;">{{mb_value object=$_acte field=montant_depassement}}</td>
              <td style="text-align: right;">-</td>
              <td style="text-align: right;">
                {{mb_value object=$_acte field=_tarif}}
              </td>
            </tr>
          {{/foreach}}
        {{/if}}

        {{if $devis->_ref_frais_divers|@count != 0}}
          <tr>
            <th colspan="7" class="title">Frais divers</th>
          </tr>
          <tr>
            <th>{{tr}}CFraisDivers-quantite{{/tr}}</th>
            <th>{{tr}}CFraisDiversType-libelle{{/tr}}</th>
            <th>{{tr}}CFraisDivers-coefficient{{/tr}}</th>
            <th></th>
            <th></th>
            <th>Hors taxe</th>
            <th>Total</th>
          </tr>
          {{foreach from=$devis->_ref_frais_divers item=_frais}}
            <tr>
              <td style="text-align: center;">{{mb_value object=$_frais field=quantite}}</td>
              <td style="text-align: center;">{{mb_value object=$_frais->_ref_type field=libelle}}</td>
              <td style="text-align: center;">{{mb_value object=$_frais field=coefficient}}</td>
              <td style="text-align: right;">-</td>
              <td style="text-align: right;">-</td>
              <td style="text-align: right;">{{mb_value object=$_frais field=montant_base}}</td>
              <td style="text-align: right;">{{mb_value object=$_frais field=montant_base}}</td>
            </tr>
          {{/foreach}}
        {{/if}}
        <tr>
          <th></th>
          <th></th>
          <th></th>
          <th>{{mb_label object=$devis field=base}}</th>
          <th>{{mb_label object=$devis field=dh}}</th>
          <th>{{mb_label object=$devis field=ht}}</th>
          <th>{{mb_label object=$devis field=_total}}</th>
        </tr>
        <tr>
          <td>Total des actes cotés</td>
          <td></td>
          <td></td>
          <td style="text-align: right;">{{mb_value object=$devis field=base}}</td>
          <td style="text-align: right;">{{mb_value object=$devis field=dh}}</td>
          <td style="text-align: right;">{{mb_value object=$devis field=ht}}</td>
          <td style="text-align: right;">{{mb_value object=$devis field=_total}}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>