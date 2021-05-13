{{*
 * @package Mediboard\Admissions
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var="patient" value=$_sejour->_ref_patient}}

<td>
  <form name="editAffFrm{{$_aff->_id}}" action="?m=dPadmissions" method="post">
    <input type="hidden" name="m" value="dPhospi" />
    <input type="hidden" name="dosql" value="do_affectation_aed" />
    {{if $type_externe == "depart"}}
      {{if $_aff->_ref_prev->_id}}
        {{assign var=_affectation value=$_aff->_ref_prev}}
      {{else}}
        {{assign var=_affectation value=$_aff}}
      {{/if}}
      
      {{mb_key object=$_affectation}}
      {{if $_affectation->effectue}}
        <input type="hidden" name="effectue" value="0" />
        <button type="button" class="cancel me-secondary" onclick="onSubmitFormAjax(this.form, { onComplete: function() {reloadPermission()} })">Annuler le départ</button>
      {{else}}
        <input type="hidden" name="effectue" value="1" />
        <button type="button" class="tick me-primary" onclick="onSubmitFormAjax(this.form, { onComplete: function() {reloadPermission()} })">Valider le départ</button>
      {{/if}}
    {{else}}
      {{mb_key object=$_aff}}
      {{if $_aff->effectue}}
        <input type="hidden" name="effectue" value="0" />
        <button type="submit" class="cancel">Annuler le retour</button>
      {{else}}
        <input type="hidden" name="effectue" value="1" />
        <button type="submit" class="tick">Valider le retour</button>
      {{/if}}
    {{/if}}
  </form>
</td>

<td colspan="2" class="text">
  <span class="CPatient-view" onmouseover="ObjectTooltip.createEx(this, '{{$patient->_guid}}');">
    {{$patient}}
  </span>
</td>

<td class="text">
  {{mb_include module=mediusers template=inc_vw_mediuser mediuser=$_sejour->_ref_praticien}}
</td>

<td>
  <div style="float: right;">
    
  </div>
  <span onmouseover="ObjectTooltip.createEx(this, '{{$_sejour->_guid}}');">
    {{$_aff->entree|date_format:$conf.time}}
  </span>
</td>

{{if $type_externe == "depart"}}
  <td class="text">
    {{$_aff->_ref_prev->_ref_lit->_view}}
  </td>
  <td class="text">
    {{$_aff->_ref_lit->_view}}
  </td>

{{else}}
  <td class="text">
    {{$_aff->_ref_lit->_view}}
  </td>
  <td class="text">
    {{if $_aff->_ref_next->_id}}
      {{$_aff->_ref_next->_ref_lit->_view}}
    {{/if}}
  </td>
{{/if}}

<td class="text" >
  {{$_aff->_duree}} jour(s)
</td>