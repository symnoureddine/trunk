{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}
<script>
  Main.add(function () {
    EchelleTri.showBttsValidation('{{$rpu->_can_validate_echelle}}', '{{$rpu->_can_invalidate_echelle}}');
  });
</script>

{{if $rpu->echelle_tri_valide}}
  <div class="small-warning">{{tr}}CRPU-alert-echelle_tri_valide{{/tr}}</div>
{{/if}}
<table class="form">
  <tr>
    <th class="title">{{mb_label class=CChapitreMotif field=nom}}</th>
    <th class="title">{{mb_label object=$rpu field="ccmu"}}</th>
  </tr>
  <tr>
    <td style="text-align: center;">
      <strong style="font-size: 2em;">{{mb_value object=$rpu field="code_diag"}}</strong>
      {{if !$rpu->echelle_tri_valide}}
        <button type="button" class="search notext" onclick="EchelleTri.searchMotif();" style="float: right;">{{tr}}Search{{/tr}}</button>
      {{/if}}
      {{if $rpu->code_diag && !$rpu->echelle_tri_valide}}
        <form name="deleteMotifRPU" action="#" method="post" onsubmit="return Motif.deleteDiag(this, 0);">
          {{mb_class  object=$rpu}}
          {{mb_key    object=$rpu}}
          <input type="hidden" name="code_diag" value="" />
          <button type="button" class="cancel notext" onclick="this.form.onsubmit();" style="float: right;">{{tr}}Delete{{/tr}}</button>
        </form>
      {{/if}}
    </td>
    <td style="text-align: center;">
      {{if $rpu->ccmu || $rpu->_estimation_ccmu}}
        <strong style="font-size: 2em;">{{tr}}CRPU.ccmu.{{if $rpu->ccmu}}{{$rpu->ccmu}}{{else}}{{$rpu->_estimation_ccmu}}{{/if}}-court{{/tr}}</strong> {{if !$rpu->ccmu}}(Estimation){{/if}}
      {{/if}}
      {{if $rpu->ccmu && $rpu->ccmu <=4 && $rpu->ccmu >= 1 && $rpu->code_diag && $rpu->_possible_update_ccmu && !$rpu->echelle_tri_valide}}
        <div style="float: right;">

          <form name="modifCcmuManuel" action="#" method="post" onsubmit="return onSubmitFormAjax(this);">
            {{mb_class  object=$rpu->_ref_echelle_tri}}
            {{mb_key    object=$rpu->_ref_echelle_tri}}
            <input type="hidden" name="rpu_id" value="{{$rpu->_id}}" />
            <input type="hidden" name="last_ccmu_manuel" value="{{$rpu->_ref_echelle_tri->ccmu_manuel}}" />
            <input type="hidden" name="ccmu_manuel" value="1" />
          </form>
          {{if $rpu->ccmu > 1 && $rpu->_ref_motif->degre_min < $rpu->ccmu}}
            <form name="modifCcmuRPUup" action="#" method="post" onsubmit="return Motif.changeCCMU(this);">
              {{mb_class  object=$rpu}}
              {{mb_key    object=$rpu}}
              <input type="hidden" name="ccmu" value="{{math equation="x-1" x=$rpu->ccmu}}" />
              <button type="button" class="up notext" onclick="this.form.onsubmit();">Augmenter le dégré de l'urgence</button>
            </form>
          {{/if}}
        </div>
      {{/if}}
    </td>
  </tr>
  {{if $rpu->code_diag}}
    <tr>
      <td colspan="2" style="text-align: center">
        <strong style="font-size: 1.4em;">{{$rpu->_ref_motif}}</strong>
        </td>
    </tr>
  {{/if}}
</table>

{{mb_include module=urgences template=vw_classement_cts}}