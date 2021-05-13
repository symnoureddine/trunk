{{*
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=maternite script=dossierMater ajax=1}}

{{assign var=patient           value=$grossesse->_ref_parturiente}}
{{assign var=dossier_perinatal value=$grossesse->_ref_dossier_perinat}}
{{assign var=surv_echographies value=$grossesse->_ref_surv_echographies}}
{{assign var=constantes_maman  value=$dossier_perinatal->_ref_ant_mater_constantes}}
{{assign var=dossier_medical   value=$patient->_ref_dossier_medical}}

<table class="print">
  <tr>
    <th class="title">
      <a href="#" {{if !$offline}}onclick="window.print();"{{/if}} style="font-size: 1.3em;">
        {{tr}}CGrossesse-action-Summary sheet in Maternity{{/tr}}
      </a>

      {{if !$offline}}
        <button type="button" class="not-printable notext print" onclick="window.print()" style="float:right">{{tr}}Print{{/tr}}</button>
      {{/if}}
    </th>
  </tr>
</table>

<table class="print">
  <tr>
    <td colspan="2">
      <table width="100%" style="font-size: 100%;">
        <tr>
          <th class="category" colspan="4">{{tr}}CPatient-Patient information{{/tr}}</th>
        </tr>
        <tr>
          <th>{{tr}}CPatient|f{{/tr}}</th>
          <td><strong>{{$patient->_view}}</strong></td>
          <th>{{mb_label object=$patient field=tel}}</th>
          <td>{{mb_value object=$patient field=tel}}</td>
        </tr>
        <tr>
          <th>{{mb_label object=$patient field=naissance}}</th>
          <td>
            {{mb_value object=$patient field=naissance}} ({{$patient->_age}})
          </td>
          <th>{{mb_label object=$patient field=tel2}}</th>
          <td>{{mb_value object=$patient field=tel2}}</td>
        </tr>
        <tr>
          <th></th>
          <td>
            <strong>
              {{mb_value object=$constantes_maman field=taille}} cm &mdash; {{mb_value object=$constantes_maman field=poids}} kg &mdash; {{tr}}CConstantesMedicales-_imc{{/tr}} {{mb_value object=$constantes_maman field=_imc}}
            </strong>
          </td>
          <th>{{mb_label object=$patient field=adresse}}</th>
          <td>
            {{mb_value object=$patient field=adresse}},
            {{mb_value object=$patient field=cp}} {{mb_value object=$patient field=ville}}
          </td>
        </tr>
        <tr>
          <th>{{tr}}CDossierMedical-medecin_traitant_id{{/tr}}</th>
          <td>{{$patient->_ref_medecin_traitant->_view}}</td>
        </tr>
        <tr>
          <th>{{tr}}CMedecin|pl{{/tr}}</th>
          <td>
            {{foreach from=$patient->_ref_medecins_correspondants item=_medecin_correspondant}}
              {{assign var=medecin value=$_medecin_correspondant->_ref_medecin}}
              - {{$medecin->_view}}
              <br />
            {{/foreach}}
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <table width="100%" style="font-size: 100%;">
        <tr>
          <th class="category" colspan="4">{{tr}}CGrossesse-Current pregnancy{{/tr}}</th>
        </tr>
        <tr>
          <th>{{mb_label object=$grossesse field=_semaine_grossesse}}</th>
          <td>
            <strong>
              {{$grossesse->_semaine_grossesse}} {{tr}}CGrossesse-_semaine_grossesse-court{{/tr}} + {{$grossesse->_reste_semaine_grossesse}}J
            </strong>
          </td>
          <th>{{mb_label object=$grossesse field=terme_prevu}}</th>
          <td><strong>{{'Ox\Core\CMbDT::format'|static_call:"":$grossesse->terme_prevu|date_format:"%d %B %Y"}}</strong></td>
        </tr>
        <tr>
          <th>{{mb_label class=CConsultationPostNatEnfant field=poids}}</th>
          <td>
            {{mb_ternary var=last_poids test=$last_constantes.0->poids value=$last_constantes.0->poids other=$constantes_maman->poids}}
            {{$last_poids}} kg
            {{if $last_constantes.0->poids}}
              ({{tr var1=$last_constantes.1.poids|date_format:$conf.date}}common-the %s{{/tr}})
              (<strong>{{if $difference_poids > 0}}+ {{/if}}{{$difference_poids}} kg</strong>)
            {{/if}}
          </td>
        </tr>
        <tr>
          <th>{{tr}}CGrossesse.determination_date_grossesse.ddr{{/tr}}</th>
          <td>{{mb_value object=$grossesse field=date_dernieres_regles}}</td>
          <th>{{tr}}CGrossesse-multiple-desc{{/tr}}</th>
          <td>{{mb_value object=$grossesse field=multiple}}</td>
        </tr>
        <tr>
          <th>{{mb_label object=$grossesse field=date_debut_grossesse}}</th>
          <td>{{mb_value object=$grossesse field=date_debut_grossesse}}</td>
          {{if $grossesse->multiple}}
            <th>{{mb_label object=$grossesse field=nb_foetus}}</th>
            <td>{{mb_value object=$grossesse field=nb_foetus}}</td>
          {{/if}}
        </tr>
        <tr>
          <th>{{mb_label object=$grossesse field=nb_grossesses_ant}}</th>
          <td>{{mb_value object=$grossesse field=nb_grossesses_ant}}</td>
          {{if $grossesse->multiple}}
            <th>{{tr}}CSurvEchoGrossesse-Chorionicity{{/tr}}</th>
            <td>
              {{foreach from=$surv_echographies item=_surv_echographie name=list_echo}}
                {{if $smarty.foreach.list_echo.first}}
                  {{if $_surv_echographie->mcba}}
                    {{mb_label object=$_surv_echographie field=mcba}}
                  {{elseif $_surv_echographie->mcma}}
                    {{mb_label object=$_surv_echographie field=mcma}}
                  {{elseif $_surv_echographie->bcba}}
                    {{mb_label object=$_surv_echographie field=bcba}}
                  {{/if}}
                {{/if}}
              {{foreachelse}}
                {{tr}}CDossierPerinat.type_surveillance.{{/tr}}
              {{/foreach}}
            </td>
          {{/if}}
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=ant_obst_nb_gr_acc}}</th>
          <td>{{mb_value object=$dossier_perinatal field=ant_obst_nb_gr_acc}} {{tr var1=$dossier_perinatal->ant_obst_nb_gr_cesar}}CGrossesse-including %s caesarean{{/tr}}</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td class="halfPane">
      <table width="100%" style="font-size: 100%;">
        <tr>
          <th class="category" colspan="4">{{tr}}CAntecedent|pl{{/tr}}</th>
        </tr>
        <tr>
          <td class="text">
            {{if $dossier_medical->_ref_antecedents_by_type}}
              {{foreach from=$dossier_medical->_ref_antecedents_by_type key=key_type item=_antecedent}}
                {{if $_antecedent && $key_type != "alle"}}
                  <strong>{{tr}}CAntecedent.type.{{$key_type}}{{/tr}}</strong>
                  {{foreach from=$_antecedent item=currAnt}}
                    <ul>
                      <li>
                        {{if $currAnt->appareil}}<strong>{{tr}}CAntecedent.appareil.{{$currAnt->appareil}}{{/tr}}</strong>{{/if}}
                        {{if $currAnt->date}}
                          {{mb_value object=$currAnt field=date}} :
                        {{/if}}
                        {{$currAnt->rques}} {{if $currAnt->important}}
                          <strong>({{tr}}CAntecedent-important{{/tr}})</strong>
                        {{elseif $currAnt->majeur}}
                          <strong>({{tr}}CAntecedent-majeur{{/tr}})</strong>
                        {{/if}}
                      </li>
                    </ul>
                  {{/foreach}}
                {{/if}}
              {{/foreach}}
            {{else}}
              <ul>
                <li style="font-weight: bold;">{{tr}}CAntecedent-No antecedent provided{{/tr}}</li>
              </ul>
            {{/if}}
          </td>
        </tr>
        {{* Atcd Père*}}
        <tr>
          <th class="category" colspan="4">{{tr}}CDossierPerinat-tab-Paternal antecedent|pl{{/tr}}</th>
        </tr>
        <tr>
          <td class="text">
            {{assign var=father                 value=$grossesse->_ref_pere}}
            {{assign var=father_constantes      value=$dossier_perinatal->_ref_pere_constantes}}
            {{assign var=father_dossier_medical value=$father->_ref_dossier_medical}}

            {{if ($father_constantes && $father_constantes->_id) || ($father_dossier_medical && $father_dossier_medical->_id)}}
              <ul>
                {{if $father_constantes->poids}}
                  <li>
                    {{mb_label object=$father_constantes field=poids}}: {{mb_value object=$father_constantes field=poids}} kg
                  </li>
                {{/if}}
                {{if $father_constantes->taille}}
                  <li>
                    {{mb_label object=$father_constantes field=taille}}: {{mb_value object=$father_constantes field=taille}} cm
                  </li>
                {{/if}}
                {{if $father_dossier_medical->groupe_sanguin && $father_dossier_medical->rhesus}}
                  <li>
                    {{mb_value object=$father_dossier_medical field=groupe_sanguin}} {{mb_value object=$father_dossier_medical field=rhesus}}
                  </li>
                {{/if}}
                {{if $father_dossier_medical->groupe_ok}}
                  <li>
                    {{mb_label object=$father_dossier_medical field=groupe_ok}}: {{mb_value object=$father_dossier_medical field=groupe_ok}}
                  </li>
                {{/if}}
                {{if $dossier_perinatal->pere_serologie_vih}}
                  <li>
                    {{mb_label object=$dossier_perinatal field=pere_serologie_vih}}: {{mb_value object=$dossier_perinatal field=pere_serologie_vih}}
                  </li>
                {{/if}}
                {{if $dossier_perinatal->pere_electrophorese_hb}}
                  <li>
                    {{mb_label object=$dossier_perinatal field=pere_electrophorese_hb}}: {{mb_value object=$dossier_perinatal field=pere_electrophorese_hb}}
                  </li>
                {{/if}}
                {{if $dossier_perinatal->pere_ant_herpes}}
                  <li>
                    {{mb_label object=$dossier_perinatal field=pere_ant_herpes}}: {{mb_value object=$dossier_perinatal field=pere_ant_herpes}}
                  </li>
                {{/if}}
                {{if $dossier_perinatal->pere_ant_autre}}
                  <li>
                    {{mb_label object=$dossier_perinatal field=pere_ant_autre}}: {{mb_value object=$dossier_perinatal field=pere_ant_autre}}
                  </li>
                {{/if}}
              </ul>
            {{/if}}

            {{if $father_atcd.counter > 0}}
              <strong>{{tr}}CAntecedent.type.fam{{/tr}}</strong>
              <ul>
                {{foreach from=$father_atcd.antecedents item=_field}}
                  {{if $dossier_perinatal->$_field}}
                    <li>{{tr}}CDossierPerinat-{{$_field}}{{/tr}}</li>
                  {{/if}}
                {{/foreach}}
              </ul>
            {{elseif ($father_atcd.counter == 0) && !$father_constantes->_id && !$father_dossier_medical->_id}}
              <ul>
                <li style="font-weight: bold;">{{tr}}CAntecedent-No antecedent provided{{/tr}}</li>
              </ul>
            {{/if}}
          </td>
        </tr>
      </table>
    </td>
    <td class="halfPane">
      <table width="100%" style="font-size: 100%;">
        <tr>
          <th class="category" colspan="4">{{tr}}CAntecedent-Allergie|pl{{/tr}}</th>
        </tr>
        <tr>
          <td class="text" style="font-weight: bold; font-size:130%;">
            {{if $dossier_medical->_ref_antecedents_by_type && $dossier_medical->_ref_antecedents_by_type.alle|@count}}
              <div class="small-warning">
                {{foreach from=$dossier_medical->_ref_antecedents_by_type.alle item=_antecedent_allergie}}
                  <ul>
                    <li>
                      {{if $_antecedent_allergie->date}}
                        {{mb_value object=$_antecedent_allergie field=date}} :
                      {{/if}}
                      {{$_antecedent_allergie->rques}}
                    </li>
                  </ul>
                {{/foreach}}
              </div>
            {{else}}
              <ul>
                <li style="font-size: 0.8em;">{{tr}}Allergie-No allergy provided{{/tr}}</li>
              </ul>
            {{/if}}
          </td>
        </tr>
        <tr>
          <th class="category" colspan="4">{{tr}}CDossierPerinat-Toxic products{{/tr}}</th>
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=tabac_avant_grossesse}}</th>
          <td>
            <span {{if $dossier_perinatal->tabac_avant_grossesse}}style="color: darkred;"{{/if}}>
              {{mb_value object=$dossier_perinatal field=tabac_avant_grossesse}}
            </span>
            {{if $dossier_perinatal->tabac_avant_grossesse}}
              : {{mb_value object=$dossier_perinatal field=qte_tabac_avant_grossesse}} cg/jour
            {{/if}}
          </td>
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=tabac_debut_grossesse}}</th>
          <td>
            <span {{if $dossier_perinatal->tabac_debut_grossesse}}style="color: darkred;"{{/if}}>
              {{mb_value object=$dossier_perinatal field=tabac_debut_grossesse}}
            </span>
            {{if $dossier_perinatal->tabac_debut_grossesse}}
              : {{mb_value object=$dossier_perinatal field=qte_tabac_debut_grossesse}} cg/jour
            {{/if}}
          </td>
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=alcool_debut_grossesse}}</th>
          <td>
            <span {{if $dossier_perinatal->alcool_debut_grossesse}}style="color: darkred;"{{/if}}>
              {{mb_value object=$dossier_perinatal field=alcool_debut_grossesse}}
            </span>
            {{if $dossier_perinatal->alcool_debut_grossesse}}
              : {{mb_value object=$dossier_perinatal field=qte_alcool_debut_grossesse}} verres/sem
            {{/if}}
          </td>
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=canabis_debut_grossesse}}</th>
          <td>
            <span {{if $dossier_perinatal->canabis_debut_grossesse}}style="color: darkred;"{{/if}}>
              {{mb_value object=$dossier_perinatal field=canabis_debut_grossesse}}
            </span>
            {{if $dossier_perinatal->canabis_debut_grossesse}}
              : {{mb_value object=$dossier_perinatal field=qte_canabis_debut_grossesse}} joints/sem
            {{/if}}
          </td>
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=subst_avant_grossesse}}</th>
          <td>
            <span {{if $dossier_perinatal->subst_avant_grossesse}}style="color: darkred;"{{/if}}>
              {{mb_value object=$dossier_perinatal field=subst_avant_grossesse}}
            </span>
            {{if $dossier_perinatal->subst_avant_grossesse}}
              <br />
              &mdash; {{tr}}CDossierPerinat-mode_subst_avant_grossesse-court{{/tr}} :
              {{mb_value object=$dossier_perinatal field=mode_subst_avant_grossesse}}
              <br />
              &mdash; {{mb_label object=$dossier_perinatal field=nom_subst_avant_grossesse}} :
              {{mb_value object=$dossier_perinatal field=nom_subst_avant_grossesse}}

              {{if $dossier_perinatal->subst_subst_avant_grossesse}}
                <br />
                &mdash; {{mb_label object=$dossier_perinatal field=subst_subst_avant_grossesse}} :
                {{mb_value object=$dossier_perinatal field=subst_subst_avant_grossesse}}
              {{/if}}
            {{/if}}
          </td>
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=subst_debut_grossesse}}</th>
          <td>
            <span {{if $dossier_perinatal->subst_debut_grossesse}}style="color: darkred;"{{/if}}>
              {{mb_value object=$dossier_perinatal field=subst_debut_grossesse}}
            </span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td class="halfPane">
      <table width="100%" style="font-size: 100%;">
        <tr>
          <th class="category" colspan="4">{{tr}}CDossierPerinat-traitements_sejour_mere{{/tr}}</th>
        </tr>
        <tr>
          <td class="text">
            {{if $dossier_medical->_ref_traitements || $dossier_medical->_ref_prescription}}
              <ul>
                {{if $dossier_medical->_ref_prescription}}
                  {{foreach from=$dossier_medical->_ref_prescription->_ref_prescription_lines item=_line_med}}
                    <li>
                      <a href="#1" onclick="Prescription.viewProduit(null,'{{$_line_med->code_ucd}}','{{$_line_med->code_cis}}');">
                        {{$_line_med->_ucd_view}}
                      </a>
                      {{if $_line_med->_ref_prises|@count}}
                        ({{foreach from=$_line_med->_ref_prises item=_prise name=foreach_prise}}
                        {{$_prise->_view}}{{if !$smarty.foreach.foreach_prise.last}},{{/if}}
                      {{/foreach}})
                      {{/if}}
                      {{if $_line_med->commentaire}}
                        ({{$_line_med->commentaire}})
                      {{/if}}
                      {{if $_line_med->debut || $_line_med->fin}}
                        <span class="compact">({{mb_include module=system template=inc_interval_date from=$_line_med->debut to=$_line_med->fin}})</span>
                      {{/if}}
                    </li>
                  {{/foreach}}
                {{/if}}

                {{if $dossier_medical->_ref_traitements && $dossier_medical->_ref_traitements|@count}}
                  {{foreach from=$dossier_medical->_ref_traitements item=curr_trmt}}
                    <li>
                      {{if $curr_trmt->fin}}
                        Depuis {{mb_value object=$curr_trmt field=debut}}
                        jusqu'à {{mb_value object=$curr_trmt field=fin}} :
                      {{elseif $curr_trmt->debut}}
                        Depuis {{mb_value object=$curr_trmt field=debut}} :
                      {{/if}}
                      <i>{{$curr_trmt->traitement}}</i>
                    </li>
                    {{foreachelse}}
                    {{if $dossier_medical->absence_traitement}}
                      <li>{{tr}}CTraitement.absence{{/tr}}</li>
                    {{elseif !($dossier_medical->_ref_prescription && $dossier_medical->_ref_prescription->_ref_prescription_lines|@count) && !($lines_tp|@count)}}
                      <li>{{tr}}CTraitement.none{{/tr}}</li>
                    {{/if}}
                  {{/foreach}}
                {{/if}}
              </ul>
            {{/if}}
            </td>
        </tr>
      </table>
    </td>
    <td class="halfPane">
      <table  width="100%" style="font-size: 100%;">
        <tr>
          <th class="category" colspan="4">{{tr}}CGrossesse-Stays during pregnancy{{/tr}}</th>
        </tr>
        <tr>
          <td>
            <ul>
              {{foreach from=$grossesse->_ref_sejours item=_sejour}}
                {{if $_sejour->type != "consult"}}
                  <li>{{$_sejour->_view}} &mdash; {{$_sejour->_ref_praticien}}</li>
                {{/if}}
              {{foreachelse}}
                <li>{{tr}}CSejour.none{{/tr}}</li>
              {{/foreach}}
            </ul>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <table class="print" width="100%" style="font-size: 100%;">
        <tr>
          <th class="category" colspan="2">{{tr}}CDossierPerinat-debut_grossesse-depistages{{/tr}}</th>
        </tr>
        <tr>
          <td class="halfPane">
            <table width="100%" style="font-size: 100%;">
              <tr>
                <th class="category" colspan="2">
                  {{tr}}CDepistageGrossesse-Immuno-hematology{{/tr}}
                </th>
              </tr>
              {{if $counter_depisage.immuno > 0}}
                {{foreach from=$immuno_serology.immuno key=_field item=value}}

                    <tr>
                      <th>{{mb_label class=CDepistageGrossesse field=$_field}}</th>
                      <td>
                        {{if $value}}
                          {{$value|smarty:nodefaults}}
                        {{else}}
                          &mdash;
                        {{/if}}
                      </td>
                    </tr>
                {{/foreach}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}
            </table>
          </td>
          <td class="halfPane">
            <table width="100%" style="font-size: 100%;">
              <tr>
                <th class="category" colspan="2">
                  {{tr}}CDepistageGrossesse-Serology{{/tr}}
                </th>
              </tr>

              {{if $counter_depisage.serologie > 0}}
                {{foreach from=$immuno_serology.serologie key=_field item=value}}
                  <tr>
                    <th>{{mb_label class=CDepistageGrossesse field=$_field}}</th>
                    <td>
                      {{if $value}}
                        {{$value|smarty:nodefaults}}
                      {{else}}
                        &mdash;
                      {{/if}}
                    </td>
                  </tr>
                {{/foreach}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}
            </table>
          </td>
        </tr>
        <tr>
          <td class="halfPane">
            <table width="100%" style="font-size: 100%;">
              <tr>
                <th class="category" colspan="6">
                  {{tr}}CDepistageGrossesse-Biochemistry{{/tr}} - {{tr}}CDepistageGrossesse-Hematology and Hemostasis{{/tr}}
                </th>
              </tr>

              {{if $counter_depisage.biochimie > 0}}
                <tr>
                  <td style="width: 12em; padding-right: 0;"></td>
                  {{foreach from=$grossesse->_back.depistages item=depistage}}
                    <th class="print_date_depistage" style="width: 10em;">
                      {{mb_value object=$depistage field=date}}
                      <br />
                      {{mb_value object=$depistage field=_sa}} SA
                    </th>
                  {{/foreach}}
                  <td></td>
                </tr>

                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=nfs_hb    unite=" g/dl" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=gr        unite=" /mm³" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=gb        unite=" g/L"  style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=vgm       unite=" fL"   style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=ferritine unite=" µg/l" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=glycemie  unite=" g/l"  style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=electro_hemoglobine_a1 unite=" %" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=electro_hemoglobine_a2 unite=" %" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=electro_hemoglobine_s  unite=" %" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=tp             unite=" %"           style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=tca            unite=" s"           style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=fg             unite=" g/L"         style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=nfs_plaquettes unite=" (x1000)/mm³" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=depistage_diabete style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=rques_biochimie   style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}
            </table>
          </td>
          <td class="halfPane me-valign-top">
            <table width="100%" style="font-size: 100%;">
              <tr>
                <th class="category" colspan="6">
                  {{tr}}CDepistageGrossesse-urine{{/tr}}
                </th>
              </tr>

              {{if $counter_depisage.urine > 0}}
                <tr>
                  <td style="width: 12em; padding-right: 0;"></td>
                  {{foreach from=$grossesse->_back.depistages item=depistage}}
                    <th class="print_date_depistage" style="width: 10em;">
                      {{mb_value object=$depistage field=date}}
                      <br />
                      {{mb_value object=$depistage field=_sa}} SA
                    </th>
                  {{/foreach}}
                  <td></td>
                </tr>

                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=albuminerie    unite=" g/L" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=glycosurie     unite=" g/L" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=albuminerie_24 unite=" g/L" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=cbu style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}

              <tr>
                <th class="category" colspan="6">
                  {{tr}}CDepistageGrossesse-Vasculo-Renal Assessment{{/tr}}
                </th>
              </tr>

              {{if $counter_depisage.bactero > 0}}
                <tr>
                  <td style="width: 12em; padding-right: 0;"></td>
                  {{foreach from=$grossesse->_back.depistages item=depistage}}
                    <th class="print_date_depistage" style="width: 10em;">
                      {{mb_value object=$depistage field=date}}
                      <br />
                      {{mb_value object=$depistage field=_sa}} SA
                    </th>
                  {{/foreach}}
                  <td></td>
                </tr>

                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=acide_urique unite=" mg/24h" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=asat         unite=" UI/l"   style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=alat         unite=" UI/l"   style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=phosphatase  unite=" UI/l"   style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=brb                 style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=sel_biliaire        style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=creatininemie       style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=rques_bacteriologie style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}
            </table>
          </td>
        </tr>
        <tr>
          <td class="halfPane">
            <table width="100%" style="font-size: 100%;">
              <tr>
                <th class="category" colspan="6">
                  {{tr}}CDepistageGrossesse-1er trimestre{{/tr}}
                </th>
              </tr>

              {{if $counter_depisage.trimestre1 > 0}}
                <tr>
                  <td style="width: 12em; padding-right: 0;"></td>
                  {{foreach from=$grossesse->_back.depistages item=depistage}}
                    <th class="print_date_depistage" style="width: 10em;">
                      {{mb_value object=$depistage field=date}}
                      <br />
                      {{mb_value object=$depistage field=_sa}} SA
                    </th>
                  {{/foreach}}
                  <td></td>
                </tr>

                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=marqueurs_seriques_t21 style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=dpni                   style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=dpni_rques             style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=pappa                  style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=hcg1 unite=" mUI/ml"   style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=rques_t1               style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}
            </table>
          </td>
          <td class="halfPane me-valign-top">
            <table width="100%" style="font-size: 100%;">
              <tr>
                <th class="category" colspan="6">
                  {{tr}}CDepistageGrossesse-2nd trimestre{{/tr}}
                </th>
              </tr>

              {{if $counter_depisage.trimestre2 > 0}}
                <tr>
                  <td style="width: 12em; padding-right: 0;"></td>
                  {{foreach from=$grossesse->_back.depistages item=depistage}}
                    <th class="print_date_depistage" style="width: 10em;">
                      {{mb_value object=$depistage field=date}}
                      <br />
                      {{mb_value object=$depistage field=_sa}} SA
                    </th>
                  {{/foreach}}
                  <td></td>
                </tr>

                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=afp  unite=" ng/l"   style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=hcg2 unite=" mUI/ml" style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=estriol  style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=rques_t2 style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}
            </table>
          </td>
        </tr>
        <tr>
          <td class="halfPane me-valign-top">
            <table width="100%" style="font-size: 100%;">
              <tr>
                <th class="category" colspan="6">{{tr}}CDepistageGrossesse-Custom screening|pl{{/tr}}</th>
              </tr>

              {{if $counter_depisage.custom > 0}}
                <tr>
                  <td style="width: 12em; padding-right: 0;"></td>
                  {{foreach from=$grossesse->_back.depistages item=depistage}}
                    <th class="print_date_depistage" style="width: 10em;">
                      {{mb_value object=$depistage field=date}}
                      <br />
                      {{mb_value object=$depistage field=_sa}} SA
                    </th>
                  {{/foreach}}
                  <td></td>
                </tr>

                {{foreach from=$depistage_field_customs key=index item=_depistage_field}}
                  <tr>
                    <td class="print_label" style="text-align: right;">
                      <label for="{{$index}}">{{$index}}</label>
                    </td>
                    {{foreach from=$_depistage_field key=_key item=_field}}
                      <td class="text">
                        {{$_field}}
                      </td>
                    {{/foreach}}
                  </tr>
                {{/foreach}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}
            </table>
          </td>
          <td class="halfPane me-valign-top">
            <table width="100%" style="font-size: 100%;">
              <tr>
                <th class="category" colspan="6">
                  {{tr}}General{{/tr}}
                </th>
              </tr>

              {{if $counter_depisage.general > 0}}
                <tr>
                  <td style="width: 12em; padding-right: 0;"></td>
                  {{foreach from=$grossesse->_back.depistages item=depistage}}
                    <th class="print_date_depistage" style="width: 10em;">
                      {{mb_value object=$depistage field=date}}
                      <br />
                      {{mb_value object=$depistage field=_sa}} SA
                    </th>
                  {{/foreach}}
                  <td></td>
                </tr>

                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=amniocentese style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=pvc          style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=rques_hemato style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}

              <tr>
                <th class="category" colspan="6">
                  {{tr}}CDepistageGrossesse-prelevement_vaginal{{/tr}}
                </th>
              </tr>

              {{if $counter_depisage.vaginal > 0}}
                <tr>
                  <td style="width: 12em; padding-right: 0;"></td>
                  {{foreach from=$grossesse->_back.depistages item=depistage}}
                    <th class="print_date_depistage" style="width: 10em;">
                      {{mb_value object=$depistage field=date}}
                      <br />
                      {{mb_value object=$depistage field=_sa}} SA
                    </th>
                  {{/foreach}}
                  <td></td>
                </tr>

                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=strepto_b style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=parasitobacteriologique style_label="print_label" no_value=true class_value="me-text-align-right"}}
                {{mb_include module=maternite template=depistages/inc_depistage_line_cte cte=rques_vaginal style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{else}}
                <tr>
                  <td class="empty">
                    {{tr}}CDossierPerinat.lieu_surveillance.{{/tr}}
                  </td>
                </tr>
              {{/if}}
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <table width="100%" style="font-size: 100%;">
        <tr>
          <th class="category" colspan="2">{{tr}}CAccouchement{{/tr}}</th>
        </tr>
        <tr>
          <th style="width: 6%;">{{mb_label object=$dossier_perinatal field=rques_conduite_a_tenir}}</th>
          <td>
            {{if $dossier_perinatal->rques_conduite_a_tenir}}
              {{mb_value object=$dossier_perinatal field=rques_conduite_a_tenir}}
            {{else}}
              {{tr}}CAccouchement.cesar_motif.{{/tr}}
            {{/if}}
          </td>
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=projet_allaitement_maternel}}</th>
          <td>{{mb_value object=$dossier_perinatal field=projet_allaitement_maternel}}</td>
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=projet_analgesie_peridurale}}</th>
          <td>{{mb_value object=$dossier_perinatal field=projet_analgesie_peridurale}}</td>
        </tr>
        <tr>
          <th>{{mb_label object=$dossier_perinatal field=facteur_risque}}</th>
          <td>
            {{if $dossier_perinatal->facteur_risque}}
              {{mb_value object=$dossier_perinatal field=facteur_risque}}
            {{else}}
              {{tr}}CAccouchement.cesar_motif.{{/tr}}
            {{/if}}
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <table class="print" width="100%" style="font-size: 100%;">
        <tr>
          <th class="category" colspan="2">{{tr}}CDossierPerinat-suivi_grossesse-echographies{{/tr}}</th>
        </tr>
        <tr>
          <td>
            {{foreach from=$list_children key=key_num item=echographies}}
              <table width="100%" style="font-size: 100%;">
              <tr>
                <th class="category" colspan="6">
                  {{tr}}CPatient.civilite.enf-long{{/tr}} {{$key_num}}
                </th>
              </tr>

              <tr>
                <td style="width: 12em; padding-right: 0;"></td>
                {{foreach from=$echographies item=_echographie}}
                  <th class="print_date_depistage" style="width: 10em;">
                    {{mb_value object=$_echographie field=date}}
                    <br />
                    {{mb_value object=$_echographie field=_sa}} SA &ndash; {{mb_value object=$_echographie field=type_echo}}
                  </th>
                {{/foreach}}
                <td></td>
              </tr>

              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=lcc             unite="mm" style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=cn              unite="mm" style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=bip             unite="mm" style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=pc              unite="mm" style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=dat             unite="mm" style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=pa              unite="mm" style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=lf              unite="mm" style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=lp              unite="mm" style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=dfo             unite="mm" style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=poids_foetal    unite="g"  style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=remarques       unite=""   style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=opn             unite=""   style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=avis_dan        unite=""   style_label="print_label" no_value=true class_value="me-text-align-right"}}
              {{mb_include module=maternite template=inc_surv_echo_line_cte cte=pos_placentaire unite=""   style_label="print_label" no_value=true class_value="me-text-align-right"}}
            </table>
            {{if !$offline}}
              <table width="100%">
                <tr>
                  <td>
                    <table class="main" id="graph_mos_container_{{$key_num}}">
                      <tbody class="viewported">
                      <tr>
                        {{foreach from=$all_graphs key=graph_name item=_graph name=graph_loop}}
                          {{if !$smarty.foreach.graph_loop.first && $smarty.foreach.graph_loop.iteration %2 == 1}}
                            </tr>
                            <tr>
                          {{/if}}
                          <td class="viewport width50" id="graph_mos_container_{{$smarty.foreach.graph_loop.iteration}}_child_{{$key_num}}">
                            {{mb_include module=maternite template=vw_echographie_graph graph_name=$graph_name graph_axes=$_graph.$key_num.graph_axes survEchoData=$_graph.$key_num.survEchoData num_enfant=$key_num show_select_children=0 graph_size="100%"}}
                          </td>
                        {{/foreach}}
                        {{if $list_graphs|@count %2 == 1}}
                          <td></td>
                        {{/if}}
                      </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </table>
            {{/if}}
            {{foreachelse}}
              <table width="100%" style="font-size: 100%;">
                <tr>
                  <td class="empty">
                    {{tr}}CSuiviGrossesse.echographie.{{/tr}}
                  </td>
                </tr>
              </table>
            {{/foreach}}
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>


