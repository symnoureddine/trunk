{{*
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=modulateur_codes value="|"|explode:$_acte->modulateurs}}
{{assign var=class_acte_code  value="+"|str_replace:"":$_acte->code}}

<div id="line_csarr_{{$element_prescription->_id}}_{{$_sejour->_id}}_{{$acte_index}}{{$index}}" class="editLineCsarr" data-code="{{$_acte->code}}" style="min-height: 22px;">
  <table class="form">
    <tr>
      <td style="width: 10%;">
        <label>
          <input type="checkbox" name="acte_csarr_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}" value="{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}"
                 class="acte_csarr_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}} add_acte_csarr show_acte_{{$_sejour->_id}} acte_selected_{{$element_prescription->_id}}_{{$_sejour->_id}}_{{$index}}"
                 data-code="{{$_acte->code}}"
                 data-modulateurs="{{$_acte->modulateurs}}"
                 data-duree="{{$_acte->duree}}"
                 data-extension="{{$_acte->code_ext_documentaire}}"
                 data-type_seance="{{if $_acte->type_seance != 'collective'}}{{$_acte->type_seance}}{{/if}}"
                 data-executant=""
                 data-element_prescription_id="{{$element_prescription->_id}}"
                 data-event_id=""
                 data-is_selected="0"
                 data-acte_heure_debut="{{$_acte->_heure_debut}}"
                 data-acte_heure_fin="{{$_acte->_heure_fin}}"
                 data-position="{{$acte_index}}"
                 data-index="{{$index}}"
                 onchange="GroupePatient.checkElementsEnable(this, 'display_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}');
                   GroupePatient.setNotNull('{{$_acte->_id}}', '{{$_sejour->_id}}', '{{$class_acte_code}}', false, '{{$index}}');"/>

          <strong onmouseover="ObjectTooltip.createEx(this, '{{$_acte->_guid}}')">
            <code>{{$_acte->code}}</code>
          </strong>
        </label>

        {{if $_acte->default && !in_array($_sejour->_id, array_keys($sejours_associes))}}
          <script>
            Main.add(function () {
              $$('.acte_csarr_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}}')[0].checked = true;
              $$('.acte_csarr_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}}')[0].onchange();
              GroupePatient.setNotNull('{{$_acte->_id}}', '{{$_sejour->_id}}', '{{$class_acte_code}}', false, '{{$index}}');
            });
          </script>
        {{/if}}

      </td>
      <td style="width: 25%;">
        {{foreach from=$_acte->_ref_activite_csarr->_ref_modulateurs item=_modulateur}}
          <label title="{{$_modulateur->_libelle}}">
            <input type="checkbox" class="modulateur_{{$_acte->_id}}_{{$_sejour->_id}} display_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}} modulateurs_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}}"
                   name="modulateurs_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}"
                   {{if in_array($_modulateur->modulateur, $modulateur_codes)}}checked{{/if}} disabled
                   value="{{$_modulateur->modulateur}}"
                   onchange="GroupePatient.addValues(this.form, '{{$_acte->_id}}', '{{$_sejour->_id}}', 'modulateurs', '{{$index}}');" />
             {{$_modulateur->modulateur}}
          </label>
        {{/foreach}}
      </td>
      <td style="width: 11%;">
        <input type="number" class="acte_csarr_duree display_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}} duree_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}}"
               name="duree_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}"
               value="{{$_acte->duree}}" style="width: 50px; margin-left: 10px;" disabled
               onchange="GroupePatient.addValues(this.form, '{{$_acte->_id}}', '{{$_sejour->_id}}', 'duree', '{{$index}}'); GroupePatient.changeTimings(this.up('div').down('input'), null, '{{$actes_csarr|@count}}', 'line_csarr_{{$element_prescription->_id}}_{{$_sejour->_id}}', '{{$index}}');"/>
        <script>
          Main.add(function() {
            $$('input[name="duree_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}"]')[0].addSpinner({min: 1, step: 1});
          });
        </script>
      </td>
      <td>
        <span id="heure_debut_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}">
          {{$_acte->_heure_debut|date_format:$conf.time}}
        </span> <br>
        <span id="heure_fin_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}">
          {{$_acte->_heure_fin|date_format:$conf.time}}
        </span>
      </td>
      <td style="width: 15%;">
        <label style="padding-left: 10px;">
          <select name="extension_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}" style="width: 130px;" disabled
                  class="display_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}} extension_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}}"
                  onchange="GroupePatient.addValues(this.form, '{{$_acte->_id}}', '{{$_sejour->_id}}', 'extension', '{{$index}}');">
            <option value="">&dash; {{tr}}CActeCsARR-extension{{/tr}}</option>
              {{foreach from=$extensions_doc item=_extension}}
                <option {{if $_acte->code_ext_documentaire == $_extension->code}}selected{{/if}}
                        value="{{$_extension->code}}">
                    {{$_extension->_view}}
                </option>
              {{/foreach}}
          </select>
        </label>
      </td>
      <td style="width: 15%;">
        <label style="padding-left: 10px;">
          <select name="type_seance_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}" style="width: 130px;" disabled
                  class="display_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}} type_seance_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}}"
                  onchange="GroupePatient.addValues(this.form, '{{$_acte->_id}}', '{{$_sejour->_id}}', 'type_seance', '{{$index}}');">
            <option value="" {{if !$_acte->type_seance}}selected{{/if}}>
              &dash; {{tr}}CElementPrescriptionToCsarr-type_seance{{/tr}}
            </option>
            <option value="dediee" {{if $_acte->type_seance == "dediee"}}selected{{/if}}>
              {{tr}}CElementPrescriptionToCsarr.type_seance.dediee{{/tr}}
            </option>
            <option value="non_dediee" {{if $_acte->type_seance == "non_dediee"}}selected{{/if}}>
              {{tr}}CElementPrescriptionToCsarr.type_seance.non_dediee{{/tr}}
            </option>
          </select>
        </label>
      </td>
      <td style="width: 15%;">
        <label id="label_executant_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}}" style="margin: 0 5px;" class="notNull"></label>
        <select name="executant_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}" style="width: 130px;" disabled
                class="display_{{$_acte->_id}}_{{$_sejour->_id}}_{{$index}}
                       executant_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}}
                       executant_{{$_sejour->_id}}_{{$element_prescription->_id}}_{{$index}} executant_element_{{$_sejour->_id}}_{{$element_prescription->_id}}"
                onchange="GroupePatient.setNotNull('{{$_acte->_id}}', '{{$_sejour->_id}}', '{{$class_acte_code}}', true, '{{$index}}');">
          <option value="">&dash; {{tr}}Choose{{/tr}}</option>
          {{foreach from=$executants item=_executant}}
            <option {{if $_executant->_id == $app->_ref_user->_id}}selected{{/if}}
                    value="{{$_executant->_id}}">
               {{$_executant->_view}}
            </option>
          {{/foreach}}
        </select>
        <button type="button"  style="display: none;" class="notext duplicate me-tertiary" id="duplicateReeducateur_{{$class_acte_code}}_{{$_sejour->_id}}_{{$index}}"
                onclick="GroupePatient.duplicateReeducateur(this, '{{$_sejour->_id}}', '{{$element_prescription->_id}}');">
          {{tr}}CPlageGroupePatient-action-DupliduplicateReeducateur{{/tr}}
        </button>
      </td>
    </tr>
  </table>
</div>
