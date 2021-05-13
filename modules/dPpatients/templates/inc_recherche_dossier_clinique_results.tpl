{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=cabinet script=edit_consultation ajax=$ajax}}

<script>
  Main.add(function () {
    Consultation.moduleConsult = 'oxCabinet';
  });
</script>

<div style="width: 100%; padding-bottom: 5px; height: 20px;" class="not-printable">
  {{if $one_field}}
  <button type="button" style="float: left;" class="hslip me-tertiary" onclick="exportResults();">Export Texte</button>
  <button type="button" style="float: left;" class="print me-tertiary" onclick="modal_results.print();">{{tr}}Print{{/tr}}
    {{/if}}
</div>

{{if !$one_field}}
  <div class="small-info">
    Veuillez renseigner au moins un champ dans le formulaire de gauche pour effectuer une recherche
  </div>
{{else}}

  {{mb_include module=system template=inc_pagination
  total=$count_patient change_page="changePage" step=30 current=$start}}

  {{if $from || $to}}
    <h1 style="text-align: center; page-break-before: avoid;">
      {{if $from}}
        {{if $to}}
          Période du {{$from|date_format:$conf.date}} au {{$to|date_format:$conf.date}}
        {{else}}
          {{tr}}date.From_long{{/tr}} {{$from|date_format:$conf.date}}
        {{/if}}
      {{elseif $to}}
        {{tr}}date.To_long{{/tr}} {{$to|date_format:$conf.date}}
      {{/if}}
    </h1>
  {{/if}}
  <table class="main tbl">
    <tr>
      <th>{{mb_label class=CSejour field=patient_id}}</th>
      <th>Age à l'époque</th>
      <th>Dossier Médical</th>
      <th>Evénement</th>
      <th>Prescription</th>
      <th>DCI</th>
      <th>Code ATC</th>
      <th>Libelle ATC</th>
      <th>Commentaire / Motif</th>
    </tr>
    {{foreach from=$list_patient item=_patient}}
      <tr>
        <td>
        <span onmouseover="ObjectTooltip.createEx(this, '{{$_patient->_guid}}')">
          {{$_patient->_view}} ({{$_patient->sexe|strtoupper}})
        </span>
        </td>
        <td>
          {{if isset($_patient->_age_epoque|smarty:nodefaults)}}
            {{$_patient->_age_epoque}} ans
          {{else}}
            {{$_patient->_age}}
          {{/if}}
        </td>
        <td class="text compact">
          {{if isset($_patient->_ref_antecedent|smarty:nodefaults)}}
            {{assign var=atcd value=$_patient->_ref_antecedent}}
            <strong>
              {{if $atcd->type == "alle"}}
                Allergie :
              {{else}}
                Antécédent :
              {{/if}}
            </strong>
            <br />
            <span onmouseover="ObjectTooltip.createEx(this, '{{$atcd->_guid}}')">
            {{$atcd}}
          </span>
          {{else}}
            {{if isset($_patient->_refs_antecedents|smarty:nodefaults) && $_patient->_refs_antecedents|@count}}
              <strong>
                {{tr}}CAntecedent.more{{/tr}} :
              </strong>
              <ul>
                {{foreach from=$_patient->_refs_antecedents item=_atcd}}
                  {{if $_atcd->type != "alle"}}
                    <li>
                    <span onmouseover="ObjectTooltip.createEx(this, '{{$_atcd->_guid}}')">
                      {{$_atcd}}
                    </span>
                    </li>
                  {{/if}}
                {{/foreach}}
              </ul>
            {{/if}}
            {{if isset($_patient->_refs_allergies|smarty:nodefaults) && $_patient->_refs_allergies|@count}}
              <strong>
                Allergies :
              </strong>
              <ul>
                {{foreach from=$_patient->_refs_allergies item=_allergie}}
                  <li>
                  <span onmouseover="ObjectTooltip.createEx(this, '{{$_allergie->_guid}}')">
                    {{$_allergie}}
                  </span>
                  </li>
                {{/foreach}}
              </ul>
            {{/if}}
            {{if isset($_patient->_ext_codes_cim|smarty:nodefaults) && $_patient->_ext_codes_cim|@count}}
              <strong>
                Diagnostics CIM :
              </strong>
              <ul>
                {{foreach from=$_patient->_ext_codes_cim item=_ext_code_cim}}
                  <li>
                    {{$_ext_code_cim->code}} : {{$_ext_code_cim->libelle}}
                  </li>
                {{/foreach}}
              </ul>
            {{/if}}

            {{* Pathologies and problems *}}
            {{if $_patient->_ref_dossier_medical->_ref_pathologies}}
              {{assign var=pathologies value=$_patient->_ref_dossier_medical->_ref_pathologies}}

              {{if 'Ox\Mediboard\Patients\CPathologie::amountPathologies'|static_call:$pathologies > 0}}
                <strong>Pathologies :</strong>
                <ul>
                  {{foreach from=$_patient->_ref_dossier_medical->_ref_pathologies item=_pathologie}}
                    {{if $_pathologie->type == "pathologie"}}
                      <li>{{$_pathologie->code_cim10}} : {{$_pathologie}}</li>
                    {{/if}}
                  {{/foreach}}
                </ul>
              {{/if}}

              {{if 'Ox\Mediboard\Patients\CPathologie::amountProblems'|static_call:$pathologies > 0}}
                <strong>Problèmes :</strong>
                <ul>
                  {{foreach from=$_patient->_ref_dossier_medical->_ref_pathologies item=_pathologie}}
                    {{if $_pathologie->type == "probleme"}}
                      <li>{{$_pathologie->code_cim10}} : {{$_pathologie}}</li>
                    {{/if}}
                  {{/foreach}}
                </ul>
              {{/if}}
            {{/if}}
          {{/if}}
        </td>
        <td>
          {{if isset($_patient->_distant_object|smarty:nodefaults)}}
            {{assign var=object value=$_patient->_distant_object}}
            {{if $object|instanceof:'Ox\Mediboard\Cabinet\CConsultation'}}
              <span onmouseover="ObjectTooltip.createEx(this, '{{$object->_guid}}')">
              Consultation du {{$object->_ref_plageconsult->date|date_format:$conf.date}} à {{mb_value object=$object field=heure}}
            </span>
            {{elseif $object|instanceof:'Ox\Mediboard\PlanningOp\CSejour'}}
              <span onmouseover="ObjectTooltip.createEx(this, '{{$object->_guid}}')">
              {{$object->_view}}
            </span>
              <br />
              &mdash;
              <strong>{{$object->_motif_complet}} </strong>
            {{else}}
              <span onmouseover="ObjectTooltip.createEx(this, '{{$object->_guid}}')">
              {{$object->_view}}
            </span>
              <br />
              &mdash;
              <strong>{{$object->libelle}} </strong>
            {{/if}}
          {{else}}
            &mdash;
          {{/if}}
        </td>

        {{if isset($_patient->_distant_line|smarty:nodefaults)}}
          {{assign var=line value=$_patient->_distant_line}}
          <td>
          <span onmouseover="ObjectTooltip.createEx(this, '{{$line->_guid}}')">
            {{$line->_ucd_view}}
          </span>
          </td>
          <td class="text">
            {{$line->_ref_produit->_dci_view}}
          </td>
          <td>
            {{$line->_ref_produit->_ref_ATC_5_code}}
          </td>
          <td>
            {{$line->_ref_produit->_ref_ATC_5_libelle}}
          </td>
          <td>
            {{$line->commentaire}}
          </td>
        {{else}}
          <td colspan="5">&mdash;</td>
        {{/if}}
      </tr>
      {{foreachelse}}
      <tr>
        <td class="empty" colspan="9">
          Aucun résultat
        </td>
      </tr>
    {{/foreach}}
  </table>
{{/if}}