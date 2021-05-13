{{*
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=planningOp script=protocole_dhe ajax=true}}

<table class="main" style="min-width: 400px; border-spacing: 0px;">
  {{if $role == 'anesth' || $role == 'chir'}}
    {{mb_include module=planningOp template=inc_protocole_coding_ccam}}
  {{elseif $role == 'ngap'}}
    {{mb_include module=planningOp template=inc_protocole_coding_ngap}}
  {{/if}}
  <tr>
    <td class="button">
      <form name="applyCodage" action="?" method="post" onsubmit="return onSubmitFormAjax(this, {onComplete: function() {Control.Modal.close();}});">
        <input type="hidden" name="m" value="ccam" />
        <input type="hidden" name="dosql" value="do_apply_model_codage_aed" />
        <input type="hidden" name="apply" value="0"/>
        <input type="hidden" name="export" value="0"/>
        <input type="hidden" name="model_codage_id" value="{{$subject->_id}}" />

        <button type="button" class="tick"
                onclick="ProtocoleDHE.codes.setCodage('{{$object_class}}', '{{$role}}', '{{$subject->_guid}}'); this.form.onsubmit();">
          Appliquer
        </button>
        <button type="button" class="cancel" onclick="this.form.onsubmit();">Annuler</button>
      </form>
    </td>
  </tr>
</table>
