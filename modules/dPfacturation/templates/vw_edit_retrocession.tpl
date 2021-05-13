{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
changeType = function() {
  var form = getForm("Edit-CRetrocession");
  if (form.type.value == "autre") {
    $('see_pct_pm').show();
    $('see_pct_pt').show();
    $('see_montant').hide();
    form.montant.value = 0;
  }
  else {
    $('see_montant').show();
    $('see_pct_pm').hide();
    form.pct_pm.value = 0;
    $('see_pct_pt').hide();
    form.pct_pt.value = 0;
  }
}

changeClass = function() {
  var form = getForm("Edit-CRetrocession");
  if (form.code_class.value == 'CActeTarmed') {
    codeTarmed();
    $('code_caisse').hide();
    $('code_tarmed').show();
  }
  if (form.code_class.value == 'CActeCaisse') {
    codeCaisse();
    $('code_tarmed').hide();
    $('code_caisse').show();
  }
}

codeTarmed = function() {
  var form = getForm("Edit-CRetrocession");
  // Autocomplete Tarmed
  var url = new Url("tarmed", "ajax_do_tarmed_autocomplete");
  url.autoComplete(form.code, 'code_auto_complete', {
    minChars: 0,
    dropdown: true,
    select: "newcode",
    updateElement: function(selected) {
      $V(form.code, selected.down(".newcode").getText(), false);
    }
  });
}

codeCaisse = function() {
  var form = getForm("Edit-CRetrocession");
  // Autocomplete Caisse
  var url2 = new Url("tarmed", "ajax_do_prestation_autocomplete");
    url2.autoComplete(form.code_caisse, 'code_auto_complete_caisse', {
    minChars: 0,
    dropdown: true,
    select: "newcode",
    updateElement: function(selected) {
      $V(form.code_caisse, selected.down(".newcode").getText(), false);
      $V(form.code, selected.down(".newcode").getText(), false);
    }
  });
}

Main.add(function () {
  var form = getForm("Edit-CRetrocession");
  {{if $retrocession->_id}}
    changeClass();
  {{/if}}
  {{if "dPccam codage use_cotation_ccam"|gconf}}
    form.code_class.options[3].hide();
    form.code_class.options[4].hide();
    form.code_class.options[3].disabled=true;
    form.code_class.options[4].disabled=true;
  {{elseif "tarmed"|module_active && 'tarmed CCodeTarmed use_cotation_tarmed'|gconf}}
    form.code_class.options[1].hide();
    form.code_class.options[2].hide();
    form.code_class.options[1].disabled=true;
    form.code_class.options[2].disabled=true;
  {{/if}}
});
</script>

<form name="Edit-CRetrocession" action="?m={{$m}}" method="post" onsubmit="Retrocession.submit(this);">
  {{mb_key    object=$retrocession}}
  {{mb_class  object=$retrocession}}
  <input type="hidden" name="del" value="0"/>
  <table class="form">
  {{mb_include module=system template=inc_form_table_header object=$retrocession}}
    <tr>
      <th>{{mb_label object=$retrocession field=praticien_id}}</th>
      <td>{{mb_field object=$retrocession field=praticien_id options=$listPrat}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$retrocession field=nom}}</th>
      <td>{{mb_field object=$retrocession field=nom}}</td>
    </tr>
  <tr>
    <th>{{mb_label object=$retrocession field=type}}</th>
    <td>{{mb_field object=$retrocession field=type onchange="changeType();"}}</td>
  </tr>
  <tr id="see_montant" {{if $retrocession->type == "autre"}} style="display:none;"{{/if}}>
    <th>{{mb_label object=$retrocession field=valeur}}</th>
    <td>{{mb_field object=$retrocession field=valeur}}</td>
  </tr>
  <tr id="see_pct_pm" {{if $retrocession->type != "autre"}} style="display:none;"{{/if}}>
    <th>{{mb_label object=$retrocession field=pct_pm}}</th>
    <td>{{mb_field object=$retrocession field=pct_pm}}</td>
  </tr>
  <tr id="see_pct_pt" {{if $retrocession->type != "autre"}} style="display:none;"{{/if}}>
    <th>{{mb_label object=$retrocession field=pct_pt}}</th>
    <td>{{mb_field object=$retrocession field=pct_pt}}</td>
  </tr>
  <tr>
    <th>{{mb_label object=$retrocession field=code_class}}</th>
    <td>{{mb_field object=$retrocession field=code_class onchange="changeClass();" emptyLabel="Choose"}}</td>
  </tr>
  <tr>
    <th class="narrow">{{mb_label class=CFactureItem field=code}}</th>
    <td id="code_tarmed">
      <input type="text" name="code" value="{{$retrocession->code}}" style="width:250px;" />
      <div  class="autocomplete" id="code_auto_complete" style="display: none;"></div>
    </td>
    <td id="code_caisse" style="display:none;">
      <input type="text" name="code_caisse" value="{{$retrocession->code}}" style="width:250px;" onchange="this.form.code.value = this.value;"/>
      <div  class="autocomplete" id="code_auto_complete_caisse" style="display: none;"></div>
    </td>
  </tr>
  <tr>
    <th>{{mb_label object=$retrocession field=use_pm}}</th>
    <td>{{mb_field object=$retrocession field=use_pm}}</td>
  </tr>
  <tr>
    <th>{{mb_label object=$retrocession field=active}}</th>
    <td>{{mb_field object=$retrocession field=active}}</td>
  </tr>
  <tr>
    <td class="button" colspan="2">
      {{if $retrocession->_id}}
      <button class="submit" type="button" onclick="Retrocession.submit(this.form);">{{tr}}Save{{/tr}}</button>
      <button class="trash" type="button" onclick="Retrocession.confirmDeletion(this.form);">{{tr}}Delete{{/tr}}</button>
      {{else}}
      <button class="submit" type="button" onclick="Retrocession.submit(this.form);">{{tr}}Create{{/tr}}</button>
      {{/if}}
    </td>
  </tr>
  </table>
</form>