{{*
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}
{{mb_script module=mediusers script=mediusers_ch ajax=true}}
{{mb_script module=system script=exchange_source ajax=true}}
<script>
  Main.add(function () {
    MediusersCh.loadArchivesFacturation('{{$object->_id}}');
  });
</script>

<tr>
  <th>{{mb_label object=$object field=ean}}</th>
  <td>{{mb_field object=$object field=ean}}</td>
</tr>
<tr>
  <th>{{mb_label object=$object field=ean_base}}</th>
  <td>{{mb_field object=$object field=ean_base}}</td>
</tr>
<tr>
  <th>{{mb_label object=$object field=ean_xml_factu}}</th>
  <td>{{mb_field object=$object field=ean_xml_factu}}</td>
</tr>
<tr>
  <th>{{mb_label object=$object field=compte_ch_id}}</th>
  <td>
    {{mb_field object=$object field=compte_ch_id autocomplete="true,1,50,true,true" form=$name_form style="width:150px;"}}
    <button type="button" class="fas fa-cog notext" onclick="MediusersCh.listingComptes('{{$object->_id}}');">
      {{tr}}CMediusersCompteCh-parametrage{{/tr}}
    </button>
  </td>
</tr>
<tr>
  <th>{{mb_label object=$object field=electronic_bill}}</th>
  <td>{{mb_field object=$object field=electronic_bill}}</td>
</tr>

{{if 'tarmed CCodeTarmed use_cotation_tarmed'|gconf}}
  <script>
    Main.add(function () {
      var form = getForm("{{$name_form}}");
      var url = new Url("tarmed", "ajax_specialite_autocomplete");
      url.autoComplete(form.specialite_tarmed, null, {
        minChars: 0,
        dropdown: true,
        select: "newspec",
        updateElement: function(selected) {
          $V(form.specialite_tarmed, selected.down(".newspec").getText(), false);
        }
      });
    });
  </script>
  <tr>
    <th>{{mb_label object=$object field=specialite_tarmed}}</th>
    <td>{{mb_field object=$object field=specialite_tarmed style="width:150px;"}}</td>
  </tr>
{{/if}}

<tr>
  <th>{{mb_label object=$object field=place_tarmed}}</th>
  <td>
    <select name="place_tarmed" style="width: 150px;">
      <option value="">&mdash; Choix d'une place</option>
      {{if @$modules.tarmed->_can->read}}
        {{foreach from='Ox\Mediboard\Tarmed\CTarmed::getPlacesTarmed'|static_call:null item=_place_tarmed}}
          <option value="{{$_place_tarmed}}" {{if $object->place_tarmed == $_place_tarmed}}selected = "selected"{{/if}}>
            {{tr}}CTarmed.{{$_place_tarmed}}{{/tr}}
          </option>
        {{/foreach}}
      {{/if}}
    </select>
  </td>
</tr>

<tr>
  <th>{{mb_label object=$object field=role_tarmed}}</th>
  <td>
    <select name="role_tarmed" style="width: 150px;">
      <option value="">&mdash; Choix d'un rôle</option>
      {{if @$modules.tarmed->_can->read}}
        {{foreach from='Ox\Mediboard\Tarmed\CTarmed::getRolesTarmed'|static_call:null item=_role_tarmed}}
          <option value="{{$_role_tarmed}}" {{if $object->role_tarmed == $_role_tarmed}}selected = "selected"{{/if}}>
            {{tr}}CTarmed.{{$_role_tarmed}}{{/tr}}
          </option>
        {{/foreach}}
      {{/if}}
    </select>
  </td>
</tr>

<tr>
  <th>{{mb_label object=$object field=reminder_text}}</th>
  <td>{{mb_field object=$object field=reminder_text}}</td>
</tr>
<tr>
  <th>{{mb_label object=$object field=use_cdm}}</th>
  <td>{{mb_field object=$object field=use_cdm}}</td>
</tr>
<tr>
  <th>{{mb_label object=$object field=login_cdm}}</th>
  <td>{{mb_field object=$object field=login_cdm}}</td>
</tr>
<tr>
  <th>{{mb_label object=$object field=mdp_cdm}}</th>
  <td>
    <input type="password" name="mdp_cdm" value="{{$object->mdp_cdm}}" />
  </td>
</tr>

<tr>
  <th>{{mb_label object=$object field=num_contrat_prive}}</th>
  <td>{{mb_field object=$object field=num_contrat_prive}}</td>
</tr>