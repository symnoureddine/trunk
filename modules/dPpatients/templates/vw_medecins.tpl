{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=edit_for_admin value="dPpatients CMedecin edit_for_admin"|gconf}}
{{assign var=function_distinct value=$conf.dPpatients.CPatient.function_distinct}}

<script>
  setClose = function (id, view) {
    Medecin.set(id, view);
    Control.Modal.close();
  };

  var formVisible = false;

  function showAddCorres() {
    if (!formVisible) {
      $('addCorres').show();
      getForm('editFrm').focusFirstElement();
      formVisible = true;
    } else {
      hideAddCorres();
    }
  }

  function hideAddCorres() {
    $('addCorres').hide();
    formVisible = false;
  }


  function onSubmitCorrespondant(form) {
    return onSubmitFormAjax(form, {
      onComplete: function () {
        hideAddCorres();
        var formFind = getForm('find_medecin');
        formFind.elements.medecin_nom.value = form.elements.nom.value;
        formFind.elements.medecin_prenom.value = form.elements.prenom.value;
        formFind.elements.medecin_cp.value = form.elements.cp.value;
        formFind.submit();
      }
    });
  }
</script>


{{if !$annuaire}}
<form name="fusion_medecin" action="?" method="get">
  <input type="hidden" name="m" value="system" />
  <input type="hidden" name="a" value="object_merger" />
  <input type="hidden" name="objects_class" value="CMedecin" />
  <input type="hidden" name="readonly_class" value="true" />
  {{/if}}

  {{mb_include module=system template=inc_pagination current=$start_med step=$step_med total=$count_medecins change_page=refreshPageMedecin}}

  <table class="tbl">
    {{if $annuaire}}
      <tr>
        <th class="title" colspan="20">Annuaire interne</th>
      </tr>
    {{/if}}
    <tr>
      {{if $can->admin || !$edit_for_admin}}
        {{if !$annuaire}}
          <th class="narrow">
            <button type="button" onclick="Medecin.doMerge('fusion_medecin');" class="merge notext compact me-tertiary" title="{{tr}}Merge{{/tr}}">
              {{tr}}Merge{{/tr}}
            </button>
          </th>
          <th class="category narrow"></th>
          {{if $is_admin && $function_distinct}}
            {{if $function_distinct == 1}}
              <th>{{mb_title class=CMedecin field=function_id}}</th>
            {{else}}
              <th>{{mb_title class=CMedecin field=group_id}}</th>
            {{/if}}
          {{/if}}
        {{else}}
          <th>{{tr}}Import{{/tr}}</th>
        {{/if}}
      {{/if}}
      <th>{{mb_title class=CMedecin field=nom}}</th>
      <th class="narrow">{{mb_title class=CMedecin field=sexe}}</th>
      <th>{{mb_title class=CMedecin field=adresse}}</th>
      <th class="narrow">{{mb_title class=CMedecin field=type}}</th>
      <th>{{mb_title class=CMedecin field=disciplines}}</th>
      <th class="narrow">{{mb_title class=CMedecin field=tel}}</th>
      <th class="narrow">{{mb_title class=CMedecin field=fax}}</th>
      <th class="narrow">{{mb_title class=CMedecin field=email}}</th>
      {{if $dialog && !$annuaire}}
        <th id="vw_medecins_th_select">{{tr}}Select{{/tr}}</th>
      {{/if}}
    </tr>
    {{foreach from=$medecins item=_medecin}}
      {{assign var=medecin_id value=$_medecin->_id}}
      <tr {{if !$_medecin->actif}}class="hatching"{{/if}}>
        {{mb_ternary var=href test=$dialog value="#choose" other="?m=$m&tab=vw_correspondants&medecin_id=$medecin_id"}}

        {{if !$annuaire}}
          {{if $can->admin || !$edit_for_admin}}
            <td>
              <input type="checkbox" name="objects_id[]" value="{{$_medecin->_id}}" />
            </td>
            <td>
              <button type="button" class="edit notext me-tertiary"
                      onclick="Medecin.editMedecin('{{$_medecin->_id}}',refreshPageMedecin)">
              </button>
            </td>
          {{/if}}

          {{if $is_admin && $function_distinct}}
            <td>
            {{if $function_distinct == 1}}
              <span onmouseover="ObjectTooltip.createEx(this, '{{$_medecin->_ref_function->_guid}}')">
                {{mb_value object=$_medecin field=function_id}}
              </span>
            {{else}}
              <span onmouseover="ObjectTooltip.createEx(this, '{{$_medecin->_ref_group->_guid}}')">
                {{mb_value object=$_medecin field=group_id}}
              </span>
            {{/if}}
            </td>
          {{/if}}
        {{else}}
          <td class="button">
            <button type="button" class="import notext me-tertiary"
                    onclick="$V(getForm('find_medecin').annuaire, 0); Medecin.duplicate('{{$_medecin->_id}}', refreshPageMedecin)">
              {{tr}}Import{{/tr}}
            </button>
          </td>
        {{/if}}

        <td class="text">
          {{$_medecin->nom}} {{$_medecin->prenom|strtolower|ucfirst}}
        </td>

        <td style="text-align: center;" class="me-text-align-left {{if $_medecin->sexe == 'u'}}empty{{/if}}">{{mb_value object=$_medecin field=sexe}}</td>

        <td class="text compact">
          {{$_medecin->adresse}}<br />
          {{mb_value object=$_medecin field=cp}} {{mb_value object=$_medecin field=ville}}
        </td>

        <td style="text-align: center;" class="me-text-align-left">{{mb_value object=$_medecin field=type}}</td>
        <td class="text">{{mb_value object=$_medecin field=disciplines}}</td>
        <td style="text-align: center;" class="me-text-align-left">{{mb_value object=$_medecin field=tel}}</td>
        <td style="text-align: center;" class="me-text-align-left">{{mb_value object=$_medecin field=fax}}</td>
        <td>{{mb_value object=$_medecin field=email}}</td>

        {{if $dialog && !$annuaire}}
          <td>
            <button type="button" class="tick me-secondary"
                    onclick="setClose({{$_medecin->_id}}, '{{$_medecin->_view|smarty:nodefaults|JSAttribute}}' )">
              {{tr}}Select{{/tr}}
            </button>
          </td>
        {{/if}}
      </tr>
      {{foreachelse}}
      <tr>
        <td colspan="20" class="empty">{{tr}}CMedecin.none{{/tr}}</td>
      </tr>
    {{/foreach}}
  </table>

  {{if !$annuaire}}
</form>
{{/if}}
