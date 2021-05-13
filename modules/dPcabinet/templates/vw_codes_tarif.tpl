{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=cabinet script=tarif}}

<script>
  refreshQte = function(){
    var form = getForm("modifTarif");
    var url = new Url("tarmed", "ajax_vw_tarif_code_tarmed");
    url.addParam("type", "qte");
    url.addParam("code", $V(form.code_tarmed));
    url.addParam("date", $V(form.date));
    url.addParam("version_tarmed", $V(form.version_tarmed));
    url.requestUpdate('qtemax');
  };

  refreshCodeRef = function(){
    var form = getForm("modifTarif");
    var url = new Url("tarmed", "ajax_vw_tarif_code_tarmed");
    url.addParam("type"         , "code_ref");
    url.addParam("version_tarmed", $V(form.version_tarmed));
    url.addParam("code"         , $V(form.code_tarmed));
    url.addParam("date"         , $V(form.date));
    url.addParam("object_class" , 'CTarif');
    url.addParam("object_id"    , $V(form.tarif_id));
    url.requestUpdate('code_ref_ActeTarmed');
  };

  Main.add(function () {
  var form = getForm("modifTarif");
  {{if "tarmed"|module_active && 'tarmed CCodeTarmed use_cotation_tarmed'|gconf}}
    // Autocomplete Tarmed
    var url = new Url("tarmed", "ajax_do_tarmed_autocomplete");
    url.autoComplete(form.code_tarmed, null, {
      minChars: 0,
      dropdown: true,
      select: "newcode",
      callback: function (input, query) {
        return query+"&version_tarmed="+$V(form.version_tarmed);
      },
      updateElement: function(selected) {
        $V(form.code_tarmed, selected.down(".newcode").getText(), false);
        refreshQte();
        refreshCodeRef();
      }
    });
    // Autocomplete Caisse
    var url2 = new Url("tarmed", "ajax_do_prestation_autocomplete");
      url2.autoComplete(form.code_caisse, null, {
      minChars: 0,
      dropdown: true,
      select: "newcode",
      updateElement: function(selected) {
        $V(form.code_caisse, selected.down(".newcode").getText(), false);
      }
    });
  {{/if}}
});
</script>

<form name="modifTarif" action="?m=dPcabinet&tab=vw_edit_tarifs" method="post">
  {{mb_key    object=$tarif}}
  {{mb_class  object=$tarif}}
  <input type="hidden" name="_add_code"   value="0">
  <input type="hidden" name="_dell_code"  value="0">
  <input type="hidden" name="_code"       value="0">
  <input type="hidden" name="_quantite"   value="0">
  <input type="hidden" name="_type_code"  value="">
  <input type="hidden" name="_code_ref"  value="">
  <input type="hidden" name="_version_tarmed"  value="">
  <table class="tbl">
  {{mb_include module=system template=inc_form_table_header object=$tarif colspan="6"}}
    <tr>
      <th class="narrow">{{tr}}CActeTarmed-version_tarmed-court{{/tr}}</th>
      <th>{{tr}}CActeTarmed-code-court{{/tr}}</th>
      <th>{{tr}}CActeTarmed-quantite{{/tr}}</th>
      <th>{{tr}}CActeTarmed-code_ref{{/tr}}</th>
      <th>{{tr}}Action{{/tr}}</th>
    </tr>
    {{foreach from=$tab item=code_libelle key=nom}}
      <tr>
        <th colspan="6" class="section">{{tr}}CActeTarmed-code-court{{/tr}} {{$nom}}</th>
      </tr>
      <tr>
        <td>
          {{if $nom == "tarmed"}}
            <select name="version_tarmed">
              <option value="01_09" {{if "tarmed Version version_tarmed"|gconf == "01_09"}}selected="selected"{{/if}}>
                {{tr}}CActeTarmed.version_tarmed.01_09{{/tr}}
              </option>
              <option value="01_08_2018" {{if "tarmed Version version_tarmed"|gconf == "01_08_2018"}}selected="selected"{{/if}}>
                {{tr}}CActeTarmed.version_tarmed.01_08_2018{{/tr}}
              </option>
            </select>
          {{/if}}
        </td>
        <td>
          <input type="text" name="code_{{$nom}}" value="" style="width:250px;"/>
        </td>
        <td>
          <input type="text" name="quantite_{{$nom}}" value="1" style="width:20px;"/>
          <script>
            Main.add(function () {
              getForm("modifTarif")["quantite_{{$nom}}"].addSpinner({min:0, step:1});
            });
          </script>
          {{if $nom == "tarmed"}}
            <strong id="qtemax"></strong>
          {{/if}}
        </td>
        <td {{if $nom == "tarmed"}}id="code_ref_ActeTarmed"{{/if}}>
          {{if $nom == "tarmed"}}
            {{assign var=nom_code value=_codes_$nom}}
            <select name="code_ref_{{$nom}}">
              <option value="">&mdash; {{tr}}Choose{{/tr}}</option>
              {{foreach from=$tarif->$nom_code item=code}}
                <option value="{{$code->code}}">
                  {{$code->code}}
                </option>
              {{/foreach}}
            </select>
          {{else}}
            <input type="hidden" name="code_ref_{{$nom}}" value=""/>
          {{/if}}
        </td>
        <td class="button">
          <button onclick="Code.addCode(this.form, $V(this.form.code_{{$nom}}), $V(this.form.quantite_{{$nom}}), '{{$nom}}', $V(this.form.code_ref_{{$nom}}));"
                  class="add notext" type="button"></button>
        </td>
      </tr>
      {{assign var=nom_code value=_codes_$nom}}
      {{foreach from=$tarif->$nom_code item=code}}
        {{assign var=code_acte value=$code->code}}
        <tr>
          <td class="{{if $nom == "tarmed" && !$code->version_tarmed}}empty{{/if}} button">
            {{if $nom == "tarmed"}}
              {{if $code->version_tarmed}}
                {{mb_value object=$code field=version_tarmed}}
              {{else}}
                &ndash;
              {{/if}}
            {{/if}}
          </td>
          <td>
            <strong>{{$code_acte}}</strong>
            {{$code->$code_libelle->libelle|truncate:50:"..."}}
          </td>
          <td>{{$code->quantite}}</td>
          <td>
            {{if $nom == "tarmed"}}
              {{$code->code_ref}}
            {{/if}}
          </td>
          <td class="button">
            <button class="trash notext" type="button" onclick="Code.dellCode(this.form, '{{$code_acte}}', '{{$nom}}')"></button>
          </td>
        </tr>
      {{/foreach}}
    {{/foreach}}
    <tr>
      <td class="button" colspan="6">
        <button class="close" onclick="Code.modal.close();">{{tr}}Close{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>