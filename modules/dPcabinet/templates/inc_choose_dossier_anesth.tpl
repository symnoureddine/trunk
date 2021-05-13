{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}
{{mb_script module=cabinet script=edit_consultation}}
{{mb_default var=mod_ambu value=0}}

<script>
  Main.add(function() {
    var cpa_operation = $('cpa_'+'{{$selOp->_guid}}');
    if (cpa_operation) {
      cpa_operation.show();
    }
  });
</script>
{{assign var=csa_duplicate_by_cabinet value='dPcabinet CConsultation csa_duplicate_by_cabinet'|gconf}}
{{if $selOp->_ref_sejour->_ref_consult_anesth->_id}}
  <script type="text/javascript">
    updateOperation = function(operation_id, consult_anesth_id) {
      new Url('cabinet', 'ajax_update_operation')
        .addParam('operation_id', operation_id)
        .addParam('consult_anesth_id', consult_anesth_id)
        .requestModal(800, 250);
    };
  </script>

  {{assign var="consult_anesth" value=$selOp->_ref_sejour->_ref_consult_anesth}}

  <form name="linkConsultAnesth" action="?" method="post">
    <input type="hidden" name="m" value="cabinet" />
    <input type="hidden" name="dosql" value="do_duplicate_dossier_anesth_aed" />
    <input type="hidden" name="_consult_anesth_id" value="{{$consult_anesth->_id}}" />
    <input type="hidden" name="sejour_id" value="{{$selOp->sejour_id}}" />
    <input type="hidden" name="operation_id" value="{{$selOp->_id}}" />
    <input type="hidden" name="redirect" value="0" />
    <table class="form">
      <tr>
        <td class="text">
          <div class="big-info">
            Une consultation préanesthésique a été effectuée pour le séjour de ce patient
            le <strong>{{$consult_anesth->_date_consult|date_format:$conf.date}}</strong>
            par {{if $consult_anesth->_ref_chir->isPraticien()}}le <strong>Dr{{else}}<strong>{{/if}} {{$consult_anesth->_ref_chir->_view}}</strong>.
            Vous devez <strong>{{if !$consult_anesth->operation_id}}associer ce dossier d'anesthésie à l'intervention courante{{else}}dupliquer le dossier d'anesthésie pour le relier à l'intervention courante{{/if}}</strong> si vous désirez y accéder.
          </div>
        </td>
      </tr>
      <tr>
        <td class="button">
          {{if !$consult_anesth->operation_id}}
            <button type="button" class="submit" onclick="updateOperation('{{$selOp->_id}}', '{{$consult_anesth->_id}}');">Associer à cette intervention</button>
          {{else}}
            <button type="button" class="submit" onclick="return onSubmitFormAjax(this.form, function() {document.location.reload();});">Dupliquer et relier</button>
          {{/if}}
        </td>
      </tr>
    </table>
  </form>
  {{mb_return}}
{{/if}}

<script>
  printFiche = function(dossier_anesth_id) {
    var url = new Url("dPcabinet", "print_fiche");
    url.addParam("dossier_anesth_id", dossier_anesth_id);
    url.addParam("print", true);
    url.popup(700, 500, "printFiche");
  };
  Main.add(function(){
    if ($('anesth_tab_group')){
      $('anesth_tab_group').select('a[href=#fiche_anesth]')[0].addClassName('wrong');
    }
  });
</script>

<div class="big-info">
  Aucun dossier d'anesthésie n'a été associé à cette intervention ou ce séjour pour le patient {{$patient}}
  <br />
  Vour pouvez :
  <ul>
    <li>Soit <strong>associer un dossier d'anesthésie</strong> d'une consultation passée,</li>
    <li>Soit <strong>créer un nouveau dossier d'anesthésie</strong>.</li>
  </ul>
</div>

<table class="form">
  <tr>
    <th colspan="3" class="category">Associer un dossier existant</th>
  </tr>
  {{assign var=dossiers_anesth value=0}}
  {{foreach from=$patient->_ref_consultations item=_consultation}}
    {{if $_consultation->_refs_dossiers_anesth|@count}}
      {{assign var=dossiers_anesth value=1}}
      <tr>
        <th rowspan="{{$_consultation->_refs_dossiers_anesth|@count}}">
          {{tr}}CConsultation{{/tr}}
          du {{$_consultation->_date|date_format:$conf.date}}
        </th>
        {{if $_consultation->annule}}
          <td rowspan="{{$_consultation->_refs_dossiers_anesth|@count}}" colspan="2" class="cancelled">[Consultation annulée]</td>
        {{else}}
          {{foreach from=$_consultation->_refs_dossiers_anesth item=_dossier_anesth name=foreach_anesth}}
            {{assign var=chir_anesth value=$_dossier_anesth->_ref_chir}}
            <td class="narrow">
              {{if $chir_anesth->isPraticien()}}Dr{{/if}} {{$chir_anesth->_view}}
            </td>
            <td>
              {{if $_dossier_anesth->_ref_operation->_id}}
                Déjà associé :
                <strong>{{$_dossier_anesth->_ref_operation->_view}}</strong>
                <form name="duplicateOpFrm" action="?m={{$m}}" method="post" onsubmit="{{$onSubmit}}">
                  <input type="hidden" name="dosql" value="do_duplicate_dossier_anesth_aed" />
                  <input type="hidden" name="redirect" value="0" />
                  <input type="hidden" name="del" value="0" />
                  <input type="hidden" name="m" value="dPcabinet" />
                  <input type="hidden" name="_consult_anesth_id" value="{{$_dossier_anesth->_id}}" />
                  <input type="hidden" name="operation_id" value="{{$selOp->_id}}" />
                  <button class="link"
                          {{if !array_key_exists($chir_anesth->_id, $listAnesths) && $csa_duplicate_by_cabinet}}disabled="disabled"{{/if}}
                    >Dupliquer et associer</button>
                </form>
              {{elseif $_dossier_anesth->_ref_sejour->_id}}
                Déjà associé :
                <strong>{{$_dossier_anesth->_ref_sejour->_view}}</strong>
              {{else}}

                <form name="addOpFrm" action="?m={{$m}}" method="post" onsubmit="return onSubmitFormAjax(this,
                  function() {
                    if ($('operation_area')) {
                      loadOperation('{{$selOp->_id}}', null, null, 'anesth_tab');
                    }
                    else {
                      refreshFicheAnesth();
                    }
                  });">
                  <input type="hidden" name="dosql" value="do_consult_anesth_aed" />
                  <input type="hidden" name="del" value="0" />
                  <input type="hidden" name="m" value="dPcabinet" />
                  <input type="hidden" name="consultation_anesth_id" value="{{$_dossier_anesth->_id}}" />
                  <input type="hidden" name="operation_id" value="{{$selOp->_id}}" />
                  <button class="tick"
                          {{if !array_key_exists($chir_anesth->_id, $listAnesths) && $csa_duplicate_by_cabinet}}disabled="disabled"{{/if}}
                    >{{tr}}Associate{{/tr}}</button>
                </form>
              {{/if}}
              <button style="float:right;" type="button" class="print notext" onclick="printFiche('{{$_dossier_anesth->_id}}');"></button>
            </td>
            {{if !$smarty.foreach.foreach_anesth.last}}
              </tr>
              <tr>
            {{/if}}
          {{/foreach}}
        {{/if}}
      </tr>
    {{/if}}
  {{/foreach}}
  {{if !$patient->_ref_consultations|@count || !$dossiers_anesth}}
    <tr>
      <td><em>Aucun dossier d'anesthésie existant pour ce patient</em></td>
    </tr>
    </tr>
  {{/if}}
  {{if $create_dossier_anesth == 1 && $listAnesths|@count}}
    <tr>
      <th colspan="3" class="category">Créer un nouveau dossier</th>
    </tr>
    <tr>
      <td colspan="3" class="button">
        <form name="createConsult" action="?m={{$m}}" method="post" onsubmit="{{$onSubmit}}">
          <input type="hidden" name="dosql" value="do_consult_now" />
          <input type="hidden" name="m" value="dPcabinet" />
          <input type="hidden" name="del" value="0" />
          <input type="hidden" name="consultation_id" value="" />
          <input type="hidden" name="_operation_id" value="{{$selOp->_id}}" />
          <input type="hidden" name="sejour_id" value="{{$selOp->sejour_id}}" />
          {{if $mod_ambu}}
            <input type="hidden" name="callback" value="Consultation.editModal"/>
          {{/if}}
          <input type="hidden" name="_redirect" value="?" />
          <input type="hidden" name="patient_id" value="{{$selOp->_ref_sejour->patient_id}}" />

          <table class="form">
            <tr>
              <th>{{mb_label class=CConsultation field="_prat_id"}}</th>
              <td>
                <select name="_prat_id">
                  {{foreach from=$listAnesths item=curr_anesth}}
                  <option value="{{$curr_anesth->user_id}}" {{if $selOp->_ref_anesth->user_id == $curr_anesth->user_id}} selected="selected" {{/if}}>
                    {{$curr_anesth->_view}}
                  </option>
                  {{/foreach}}
                </select>
              </td>
            </tr>

            {{mb_include module=cabinet template=inc_ufs_charge_price}}

            <tr>
              <td class="button" colspan="2">
                <button type="submit" class="new">{{tr}}Create{{/tr}}</button>
              </td>
            </tr>
          </table>
        </form>
      </td>
    </tr>
  {{/if}}
</table>