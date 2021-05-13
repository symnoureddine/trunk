{{*
 * @package Mediboard\Pmsi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if "planSoins"|module_active}}
  {{mb_script module=soins  script=soins ajax=true}}
{{/if}}
{{mb_script module=dPpmsi script=PMSI  ajax=true}}

<td class="text CPatient-view {{if $_sejour->facture}}opacity-30{{/if}}" colspan="2" >
  {{if $canPlanningOp->read}}
    <div style="float: right;">
      {{mb_include module=hospi template=inc_button_send_prestations_sejour}}

      {{if "web100T"|module_active}}
        {{mb_include module=web100T template=inc_button_iframe}}
      {{/if}}

      {{if "softway"|module_active}}
        {{mb_include module=softway template=inc_button_synthese}}
      {{/if}}

      <button type="button" class="print notext" onclick="Admissions.showDocs('{{$_sejour->_id}}')"></button>

      {{foreach from=$_sejour->_ref_operations item=curr_op}}
        <a class="action" title="Imprimer la DHE de l'intervention" href="#1" onclick="Admissions.printDHE('operation_id', {{$curr_op->_id}}); return false;">
          {{me_img src="print.png" icon="print" class="me-primary"}}
        </a>
        {{foreachelse}}
        <a class="action" title="Imprimer la DHE du séjour" href="#1" onclick="Admissions.printDHE('sejour_id', {{$_sejour->_id}}); return false;">
          {{me_img src="print.png" icon="print" class="me-primary"}}
        </a>
      {{/foreach}}

      <a class="action" title="Modifier le séjour" href="#editDHE"
         onclick="Sejour.editModal({{$_sejour->_id}}, 0, 0, reloadSorties); return false;">
        <img src="images/icons/planning.png" />
      </a>

      {{mb_include module=system template=inc_object_notes object=$_sejour}}
    </div>
  {{/if}}

  <input type="checkbox" name="print_doc" value="{{$_sejour->_id}}"/>

  {{mb_include module=planningOp template=inc_vw_numdos nda_obj=$_sejour _show_numdoss_modal=1}}

  <span onmouseover="ObjectTooltip.createEx(this, '{{$_sejour->_ref_patient->_guid}}');">
    {{$_sejour->_ref_patient->_view}}
  </span>
</td>
<td class="text {{if $_sejour->facture}}opacity-30{{/if}}">
  {{mb_value object=$_sejour field=entree date=$date}}
</td>
<td class="{{if $_sejour->facture}}opacity-30{{/if}}">
  <span onmouseover="ObjectTooltip.createEx(this, '{{$_sejour->_guid}}');">
    {{if ($_sejour->sortie_prevue < $date_min) || ($_sejour->sortie_prevue > $date_max)}}
      {{$_sejour->sortie_prevue|date_format:$conf.datetime}}
    {{else}}
      {{$_sejour->sortie_prevue|date_format:$conf.time}}
    {{/if}}
  </span>
  {{if $_sejour->confirme}}
    {{me_img_title src="tick.png" icon="tick" class="me-success"}}
      Sortie confirmée par le praticien
    {{/me_img_title}}
  {{/if}}
</td>
<td class="text button">
  <form name="facturer-{{$_sejour->_guid}}" action="?" method="post"
        onsubmit="return onSubmitFormAjax(this, PMSI.reloadFacturationLine('{{$_sejour->_id}}'));">
    {{mb_key   object=$_sejour}}
    {{mb_class object=$_sejour}}

    <input type="hidden" name="facture" value="1"/>

    <button {{if $_sejour->facture}}disabled{{/if}} type="submit" class="tick singleclick">Facturer</button>
  </form>
  {{if "planSoins"|module_active}}
    <button {{if !$_sejour->_ref_prescription_sejour->_id}}disabled{{/if}} type="button" class="print"
            onclick="PlanSoins.printAdministrations('{{$_sejour->_ref_prescription_sejour->_id}}')">Facturation</button>
  {{/if}}
  <!-- <button type="button" class="print" disabled>CHOP</button>
  <button type="button" class="print" disabled>Tarmed</button> -->
</td>