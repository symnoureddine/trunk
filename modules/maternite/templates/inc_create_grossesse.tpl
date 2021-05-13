{{*
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=creation_mode value=1}}
{{mb_default var=standalone    value=0}}

<form name="editFormGrossesse" method="post"
      onsubmit="return onSubmitFormAjax(this {{if $grossesse->_id}}, Control.Modal.close{{/if}})">
  <input type="hidden" name="m" value="maternite" />
  {{mb_class object=$grossesse}}
  {{mb_key   object=$grossesse}}
  <input type="hidden" name="callback"
         value="{{if "maternite CGrossesse audipog"|gconf && !$creation_mode}}
         DossierMater.refreshDossierPerinat.curry('{{$grossesse->_id}}')
        {{elseif !$standalone}}
        Grossesse.afterEditGrossesse
        {{else}}
        DossierMater.reloadHistorique.curry('{{$grossesse->_id}}'){{/if}}" />
  <input type="hidden" name="del" value="0" />
  <input type="hidden" name="_patient_sexe" value="f" />

  <table class="form me-no-box-shadow">
    <tr>{{mb_include module=system template=inc_form_table_header object=$grossesse colspan="3"}}</tr>

    <tr>
      <th class="halfPane">{{mb_label object=$grossesse field=parturiente_id}}</th>
      <td colspan="2">
        {{mb_field object=$grossesse field=parturiente_id hidden=1}}
        <input type="text" style="cursor: pointer" name="_patient_view" value="{{$grossesse->_ref_parturiente}}" readonly="readonly"
               {{if !$grossesse->_id}}onclick="PatSelector.init();"{{/if}} class="me-w75"/>
      </td>
    </tr>
    <tr>
      <th>{{mb_label object=$grossesse field=rang}}</th>
      <td>{{mb_field object=$grossesse field=rang value="1" onchange="DossierMater.updateTermePrevu();"}}</td>
      <td rowspan="2">
        <div
          class="small-info text">{{tr}}CGrossesse-msg-The calculation of the expected time to know the rank of the pregnancy and the menstrual cycle of the patient{{/tr}}</div>
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$grossesse field=cycle}}</th>
      <td>
        {{mb_field object=$grossesse field=cycle value="28"
        increment=1 step=1 form=editFormGrossesse onchange="DossierMater.updateTermePrevu();"}}
        {{tr}}days{{/tr}}
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$grossesse field=date_dernieres_regles}}</th>
      <td>
        {{mb_field object=$grossesse field=date_dernieres_regles form=editFormGrossesse register=true onchange="DossierMater.updateTermePrevu();"}}
      </td>
      <td>
        <span class="compact">
          Terme prévu : <span id="terme_prevu_ddr">{{mb_value object=$grossesse field=_terme_prevu_ddr}}</span>
        </span>
        <button type="button" class="down" onclick="DossierMater.useTermePrevu('DDR');">Utiliser ce terme</button>
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$grossesse field=date_debut_grossesse}}</th>
      <td>
        {{mb_field object=$grossesse field=date_debut_grossesse form=editFormGrossesse register=true
        onchange="DossierMater.updateTermePrevu();"}}
      </td>
      <td>
        <span class="compact">
          Terme prévu : <span id="terme_prevu_debut_grossesse">{{mb_value object=$grossesse field=_terme_prevu_debut_grossesse}}</span>
          <button type="button" class="down" onclick="DossierMater.useTermePrevu('DG');">Utiliser ce terme</button>
        </span>
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$grossesse field=terme_prevu}}</th>
      <td
        colspan="2">{{mb_field object=$grossesse field=terme_prevu form=editFormGrossesse register=true onchange="DossierMater.updateSemaines();"}}</td>
    </tr>

    {{if !"maternite CGrossesse audipog"|gconf}}
      <tr>
        <th>{{mb_label object=$grossesse field=_semaine_grossesse}}</th>
        <td colspan="2"><span id="_semaine_grossesse">{{$grossesse->_semaine_grossesse}}</span></td>
      </tr>
      {{if $grossesse->datetime_accouchement}}
        <tr>
          <th>{{mb_label object=$grossesse field=_days_relative_acc}}</th>
          <td colspan="2">+{{$grossesse->_days_relative_acc}}j</td>
        </tr>
      {{/if}}
      <tr>
        <th>{{mb_label object=$grossesse field=active}}</th>
        <td colspan="2">{{mb_field object=$grossesse field=active}}</td>
      </tr>
      <tr>
        <th>{{mb_label object=$grossesse field=multiple}}</th>
        <td colspan="2">{{mb_field object=$grossesse field=multiple}}</td>
      </tr>
      <tr {{if !$grossesse->multiple}}style="display: none;"{{/if}}>
        <th>{{mb_label object=$grossesse field=nb_foetus}}</th>
        <td colspan="2">{{mb_field object=$grossesse field=nb_foetus increment=true form=editFormGrossesse min=2}}</td>
      </tr>
      <tr>
        <th>{{mb_label object=$grossesse field=allaitement_maternel}}</th>
        <td colspan="2">{{mb_field object=$grossesse field=allaitement_maternel}}</td>
      </tr>
      <tr>
        <th>{{mb_label object=$grossesse field=num_semaines}}</th>
        <td colspan="2">
          <select name="num_semaines">
            <option value="">{{tr}}None{{/tr}}</option>
            {{foreach from=$grossesse->_specs.num_semaines->_list item=_num_semaines}}
              <option value="{{$_num_semaines}}"
                      {{if $_num_semaines == $grossesse->num_semaines}}selected{{/if}}
                {{if $_num_semaines == "sup_15"}}
                  style="
                  {{if $grossesse->num_semaines == "sup_15"}}
                    background: red;
                  {{else}}
                    display: none;
                  {{/if}}
                    "
                {{/if}}
              >{{tr}}CGrossesse.num_semaines.{{$_num_semaines}}{{/tr}}</option>
            {{/foreach}}
          </select>
        </td>
      </tr>
      <tr>
        <th>{{mb_label object=$grossesse field=lieu_accouchement}}</th>
        <td colspan="2">{{mb_field object=$grossesse field=lieu_accouchement}}</td>
      </tr>
    {{/if}}
    <tr>
      <th>{{mb_label object=$grossesse field=rques}}</th>
      <td colspan="2">{{mb_field object=$grossesse field=rques form=editFormGrossesse}}</td>
    </tr>
    <tr>
      <td colspan="3" class="button">
        {{if $grossesse->_id}}
          <button type="button" class="save" onclick="this.form.onsubmit()">{{tr}}Save{{/tr}}</button>
          <button type="button" class="cancel"
                  onclick="confirmDeletion(this.form, {objName: '{{$grossesse}}', ajax: 1})">{{tr}}Delete{{/tr}}</button>
        {{else}}
          <button id="button_create_grossesse" type="button" class="save" onclick="this.form.onsubmit()">{{tr}}Create{{/tr}}</button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>
