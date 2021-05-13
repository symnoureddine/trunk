{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $rpu->_id && $rpu->_ref_cts_degre|@count}}
  {{assign var=motif value=$rpu->_ref_motif}}
  {{if $rpu->code_diag && $motif->degre_min > $rpu->_estimation_ccmu}}
    <div class="small-warning">
      {{tr}}CEchelleTri-warning_code_incoherent{{/tr}} {{$rpu->code_diag}}: <strong>
      {{if $motif->degre_min <= 1 && $motif->degre_max >=1}}[1]{{/if}}
      {{if $motif->degre_min <= 2 && $motif->degre_max >=2}}[2]{{/if}}
      {{if $motif->degre_min <= 3 && $motif->degre_max >=3}}[3]{{/if}}
      {{if $motif->degre_min <= 4 && $motif->degre_max >=4}}[4]{{/if}}</strong>
    </div>
  {{/if}}
  {{assign var=constants_list value='Ox\Mediboard\Patients\CConstantesMedicales'|static:"list_constantes"}}

  <table class="form">
    <tr>
      <th class="title" colspan="3">{{tr}}CMotif-param_vitaux{{/tr}}</th>
    </tr>
    <tr>
      <th class="category" style="width: 33%;">{{tr}}CMotifQuestion-degre{{/tr}} 1</th>
      <th class="category" style="width: 33%;">{{tr}}CMotifQuestion-degre{{/tr}} 2</th>
      <th class="category" style="width: 33%;">{{tr}}CMotifQuestion-degre{{/tr}} 3 / {{tr}}CMotifQuestion-degre{{/tr}} 4</th>
    </tr>
    <tr>
      {{if !$rpu->echelle_tri_valide}}
        {{foreach from=$rpu->_ref_cts_degre key=degre item=_ctes}}
          <td>
            <table style="width: 100%;">
              {{foreach from=$_ctes item=_cte key=key_cte}}
                <tr>
                  <td>
                    <strong>
                      <label for="{{$_cte}}" title="{{tr}}CConstantesMedicales-{{$_cte}}-desc{{/tr}}">
                        {{tr}}CConstantesMedicales-{{$_cte}}-court{{/tr}}
                      </label>
                    </strong>
                    {{if isset($rpu->_ref_latest_constantes.0->$_cte|smarty:nodefaults)}}
                      {{assign var=_params value=$constants_list.$_cte}}
                      {{if $_params.unit}}
                        <small class="opacity-50">
                          ({{$_params.unit}})
                        </small>
                      {{/if}}
                    {{/if}}
                    <span style="float: right;text-align:right;">
                  {{if isset($rpu->_ref_latest_constantes.0->$_cte|smarty:nodefaults)}}
                    {{assign var=object_cte value=$rpu->_ref_latest_constantes.0}}
                    {{if in_array($_cte, array("ta", "ta_gauche", "ta_droit"))}}
                      {{mb_value object=$object_cte field="_`$_cte`_systole"}}
                      |
                      {{mb_value object=$object_cte field="_`$_cte`_diastole"}}
                    {{elseif in_array($_cte, array("glycemie", "cetonemie"))}}
                      {{mb_value object=$object_cte field="_`$_cte`"}}
                    {{else}}
                      {{mb_value object=$object_cte field=$_cte}}
                    {{/if}}
                    {{if $_cte == "peak_flow"}}
                      <br/>
                      <small class="opacity-50" style="float: right;">
                        ({{tr}}CEchelleTri.peak_flow.predit{{/tr}} {{$key_cte}})
                      </small>
                    {{/if}}
                  {{elseif $_cte == "index_de_choc"}}
                    {{tr}}CEchelleTri.index_de_choc.{{if $degre == 2}}positif{{else}}negatif{{/if}}{{/tr}}
                  {{elseif $_cte == "liquide" || $_cte == "proteinurie"}}
                    {{mb_value object=$rpu->_ref_echelle_tri field=$_cte}}
                  {{elseif $_cte == "pupilles"}}
                    {{tr}}CEchelleTri.pupilles.{{if $degre == 2}}asymetriques{{else}}symetriques{{/if}}{{/tr}}
                  {{elseif $_cte == "reactivite_droite" || $_cte == "reactivite_gauche"}}
                    {{tr}}CEchelleTri.{{$_cte}}.{{if $degre == 2}}areactive{{else}}reactive{{/if}}{{/tr}}
                  {{/if}}
                </span>
                  </td>
                </tr>
              {{/foreach}}
            </table>
          </td>
        {{/foreach}}
      {{else}}
        {{foreach from=$rpu->_ref_constantes_by_degre item=_ctes}}
          <td>
            <table style="width: 100%;">
              {{foreach from=$_ctes item=_cte_rpu}}
                <tr>
                  <td>
                    <strong>
                      <label for="{{$_cte_rpu->name}}" title="{{tr}}CConstantesMedicales-{{$_cte_rpu->name}}-desc{{/tr}}">
                        {{tr}}CConstantesMedicales-{{$_cte_rpu->name}}-court{{/tr}}
                      </label>
                    </strong>
                    {{if $_cte_rpu->unit}}<small class="opacity-50">{{$_cte_rpu->unit}}</small>{{/if}}
                    <span style="float: right;text-align:right;">
                      {{if in_array($_cte_rpu->name, array("index_de_choc", "liquide", "proteinurie", "pupilles", "reactivite_droite", "reactivite_gauche"))}}
                        {{tr}}CEchelleTri.{{$_cte_rpu->name}}.{{$_cte_rpu->value}}{{/tr}}
                      {{elseif $_cte_rpu->name == "peak_flow"}}
                        {{assign var=values value='|'|explode:$_cte_rpu->value}}
                        {{$values.0}} <br/>
                        <small class="opacity-50" style="float: right;">
                          ({{tr}}CEchelleTri.peak_flow.predit{{/tr}} {{$values.1}})
                        </small>
                      {{else}}
                        {{$_cte_rpu->value}}
                      {{/if}}
                    </span>
                  </td>
                </tr>
              {{/foreach}}
            </table>
          </td>
        {{foreachelse}}
          <td colspan="3" class="empty">{{tr}}CConstantesMedicales.none{{/tr}}</td>
        {{/foreach}}
      {{/if}}
    </tr>
    {{if isset($rpu->_ref_latest_constantes.0->comment|smarty:nodefaults)}}
      <tr>
        <td colspan="6" class="compact">
          {{tr}}Comment{{/tr}}: {{$rpu->_ref_latest_constantes.0->comment}}
        </td>
      </tr>
    {{/if}}
    {{assign var=default_degre_cte value='Ox\Mediboard\Urgences\CRPU'|static:default_degre_cte}}
    {{if !$rpu->_ref_cts_degre[1]|@count && !$rpu->_ref_cts_degre[2]|@count && !$rpu->_ref_cts_degre[$default_degre_cte]|@count}}
      <tr>
        <td colspan="3" class="empty">{{tr}}CEchelleTri.none_cte{{/tr}}</td>
      </tr>
    {{/if}}
  </table>
{{/if}}