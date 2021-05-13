{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}
{{mb_default var=just_validation value=0}}
<span id="refresh_validation_echelle_tri">
  <script>
    Main.add(function () {
      EchelleTri.showBttsValidation('{{$rpu->_can_validate_echelle}}', '{{$rpu->_can_invalidate_echelle}}');
    });
  </script>
</span>
{{if $just_validation}}
  {{mb_return}}
{{/if}}

{{if $rpu->_ref_reponses|@count}}
  <fieldset>
    <legend>Questions définissant le degré</legend>
    <table>
      {{foreach from=$rpu->_ref_reponses_by_group item=reponses key=name_group}}
        {{assign var=libelle_distinct value=true}}
        {{if $name_group|strstr:"-"}}
          {{assign var=libelle_distinct value=false}}
        {{/if}}
        {{foreach from=$reponses item=_reponse name="responses_rpu"}}
          <tr>
            {{if $libelle_distinct || $smarty.foreach.responses_rpu.first}}
              <th {{if !$libelle_distinct}}rowspan="{{$reponses|@count}}" style="vertical-align: middle;"{{/if}}>
                <strong>
                  {{mb_label class=CMotifQuestion field=degre}}
                  {{if !$libelle_distinct}}{{$name_group}} {{else}}{{$_reponse->_ref_question->degre}}{{/if}}:
                </strong>
              </th>
            {{/if}}
            <th style="text-align: left;">
              {{mb_value object=$_reponse->_ref_question field=nom}}
            </th>
            <td>
              <form name="editReponse-{{$_reponse->_guid}}" action="?" method="post" onsubmit="return onSubmitFormAjax(this);">
                {{mb_class  object=$_reponse}}
                {{mb_key    object=$_reponse}}
                <input type="hidden" name="rpu_id" value="{{$_reponse->rpu_id}}"/>
                <label>
                  <input onclick="Motif.submitReponse(this.form);" type="radio" name="result"
                         {{if $rpu->echelle_tri_valide}}disabled{{/if}}
                         {{if $_reponse->result}}checked="checked"{{/if}} value="1" /> {{tr}}Yes{{/tr}}
                </label>
                <label>
                  <input onclick="Motif.submitReponse(this.form);" type="radio" name="result"
                         {{if $rpu->echelle_tri_valide}}disabled{{/if}}
                         {{if $_reponse->result == "0"}}checked="checked"{{/if}} value="0" />{{tr}}No{{/tr}}
                </label>
              </form>
            </td>
          </tr>
        {{/foreach}}
        {{foreachelse}}
        <tr>
          <td colspan="2" class="empty">{{tr}}CMotifQuestion.none{{/tr}}</td>
        </tr>
      {{/foreach}}
    </table>
  </fieldset>
{{/if}}