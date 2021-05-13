{{*
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=add_class value=0}}
{{assign var=constantes value=$patient->_ref_constantes_medicales}}
{{math assign=pct_width equation="100/7" format="%.2f"}}

<tr>
  <td class="me-patient-banner-info-patient" style="width: {{$pct_width}}%;">
    {{mb_include module=soins template=inc_infos_patients_soins_constante constantes=$constantes
    constante="poids" add_class=$add_class}}
  </td>
  <td class="me-patient-banner-info-patient" style="width: {{$pct_width}}%;">
    {{mb_include module=soins template=inc_infos_patients_soins_constante constantes=$constantes
    constante="taille" add_class=$add_class}}
  </td>
  <td class="me-patient-banner-info-patient" style="width: {{$pct_width}}%;">
    {{mb_include module=soins template=inc_infos_patients_soins_constante constantes=$constantes
    constante="_imc" add_class=$add_class}}
  </td>
  <td class="me-patient-banner-info-patient" style="width: {{$pct_width}}%;">
    {{mb_include module=soins template=inc_infos_patients_soins_constante constantes=$constantes
    constante="_surface_corporelle" add_class=$add_class}}
  </td>
  <td class="me-patient-banner-info-patient" style="width: {{$pct_width}}%;">
    {{if $constantes->creatininemie}}
      {{mb_include module=soins template=inc_infos_patients_soins_constante constante="creatininemie" add_class=$add_class}}
    {{elseif $constantes->mdrd}}
      {{mb_include module=soins template=inc_infos_patients_soins_constante constante="mdrd" add_class=$add_class}}
    {{elseif $constantes->clair_creatinine}}
      {{mb_include module=soins template=inc_infos_patients_soins_constante constante="clair_creatinine" add_class=$add_class}}
    {{/if}}
  </td>
  <td class="me-patient-banner-info-patient" style="width: {{$pct_width}}%;">
    <strong>{{mb_title object=$patient field=naissance}}:</strong>
    <span>{{mb_value object=$patient field=naissance}} ({{$patient->_age}})</span>
  </td>
  <td class="me-patient-banner-info-patient" style="width: {{$pct_width}}%;">
    <strong>{{mb_title object=$patient field=sexe}}:</strong>
    <span>{{mb_value object=$patient field=sexe}}</span>
  </td>
</tr>