{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=do_subject_aed value='do_model_codage_aed'}}

{{if "dPccam codage use_cotation_ccam"|gconf == "1"}}
  {{mb_script module=planningOp script=ccam_selector ajax=1}}
  {{mb_include module=salleOp template=js_codage_ccam object=$subject}}
{{/if}}

<script>
  ApplyCodage = function() {
    var url = new Url('cabinet', 'ajax_get_tarif_codage');
    url.addParam('codage_id', '{{$subject->_id}}');
    url.requestJSON(function(codage) {
        console.log(codage);
      var form = getForm('editFrm');

      if (codage.codes_ngap.length && form.codes_ngap) {
        $V(form.codes_ngap, codage.codes_ngap.join('|'));
        form.codes_ngap.next('div.codes').update();

        codage.codes_ngap.each(function(code) {
          form.codes_ngap.next('div.codes').insert(DOM.div(null, code));
        });
      }
      else if (form.codes_ngap) {
        $V(form.codes_ngap, '');
        form.codes_ngap.next('div.codes').update();
        form.codes_ngap.next('div.codes').insert(DOM.div({class: 'empty'}, $T('None')));
      }

      {{if $user->isExecutantCCAM()}}
        if (codage.codes_ccam.length && form.codes_ccam) {
          $V(form.codes_ccam, codage.codes_ccam.join('|'));
          form.codes_ccam.next('div.codes').update();

          codage.codes_ccam.each(function(code) {
            form.codes_ccam.next('div.codes').insert(DOM.div(null, code));
          });
        }
        else if (form.codes_ccam) {
          $V(form.codes_ccam, '');
          form.codes_ccam.next('div.codes').update();
          form.codes_ccam.next('div.codes').insert(DOM.div({class: 'empty'}, $T('None')));
        }
      {{/if}}

      if (codage.codes_lpp.length && form.codes_lpp) {
        $V(form.codes_lpp, codage.codes_lpp.join('|'));
        form.codes_lpp.next('div.codes').update();

        codage.codes_lpp.each(function(code) {
          form.codes_lpp.next('div.codes').insert(DOM.div(null, code));
        });
      }
      else if (form.codes_lpp) {
        $V(form.codes_lpp, '');
        form.codes_lpp.next('div.codes').update();
        form.codes_lpp.next('div.codes').insert(DOM.div({class: 'empty'}, $T('None')));
      }

      if (codage.codes_tarmed.length && form.codes_tarmed) {
        $V(form.codes_tarmed, codage.codes_tarmed.join('|'));
        form.codes_tarmed.next('div.codes').update();

        codage.codes_tarmed.each(function(code) {
          form.codes_tarmed.next('div.codes').insert(DOM.div(null, code));
        });
      }
      else if (form.codes_tarmed) {
        $V(form.codes_tarmed, '');
        form.codes_tarmed.next('div.codes').update();
        form.codes_tarmed.next('div.codes').insert(DOM.div({class: 'empty'}, $T('None')));
      }

      if (codage.codes_caisse.length && form.codes_caisse) {
        $V(form.codes_caisse, codage.codes_caisse.join('|'));
        form.codes_caisse.next('div.codes').update();

        codage.codes_caisse.each(function(code) {
          form.codes_caisse.next('div.codes').insert(DOM.div(null, code));
        });
      }
      else if (form.codes_caisse) {
        $V(form.codes_caisse, '');
        form.codes_caisse.next('div.codes').update();
        form.codes_caisse.next('div.codes').insert(DOM.div({class: 'empty'}, $T('None')));
      }

      $V(form.secteur1, codage.secteur1, true);
      $V(form.secteur2, codage.secteur2, true);

      getForm('editModelCodage').onsubmit();
    });
  };

  Main.add(function() {
    var tabsActes = Control.Tabs.create('tab-actes', false);
  });
</script>

<div id="actes">
  <ul id="tab-actes" class="control_tabs">
    {{if "dPccam codage use_cotation_ccam"|gconf == "1"}}
      {{if $user->isExecutantCCAM()}}
        <li>
          <a href="#ccam"{{if $subject->_ref_actes_ccam|@count == 0}} class="empty"{{/if}}>
            {{tr}}CActeCCAM{{/tr}}
            <small id="count_ccam_{{$subject->_guid}}">({{$subject->_ref_actes_ccam|@count}})</small>
          </a>
        </li>
    {{/if}}
      <li>
        <a href="#ngap"{{if $subject->_ref_actes_ngap|@count == 0}} class="empty"{{/if}}>
          {{tr}}CActeNGAP|pl{{/tr}}
          <small id="count_ngap_{{$subject->_guid}}">({{$subject->_ref_actes_ngap|@count}})</small>
        </a>
      </li>
    {{/if}}
    {{if 'lpp'|module_active && "lpp General cotation_lpp"|gconf}}
      <li>
        <a href="#lpp" {{if $subject->_ref_actes_lpp|@count ==0}} class="empty"{{/if}}>
          {{tr}}CActeLPP|pl{{/tr}}
          <small id="count_lpp_{{$subject->_guid}}">({{$subject->_ref_actes_lpp|@count}})</small>
        </a>
      </li>
    {{/if}}

    {{if @$modules.tarmed->_can->read && 'tarmed CCodeTarmed use_cotation_tarmed'|gconf}}
      <li><a href="#tarmed_tab">{{tr}}CTarmed{{/tr}}</a></li>
      <li><a href="#caisse_tab">{{tr}}CCaisseMaladie|pl{{/tr}}</a></li>
    {{/if}}
  </ul>

  {{if "dPccam codage use_cotation_ccam"|gconf == "1"}}
    {{if $user->isExecutantCCAM()}}
      <div id="ccam" style="display: none;">
        {{assign var="module" value="dPcabinet"}}
        {{mb_include module=salleOp template=inc_codage_ccam}}
      </div>
    {{/if}}

    <div id="ngap" style="display: none;">
      <div id="listActesNGAP" data-object_id="{{$subject->_id}}" data-object_class="{{$subject->_class}}">
        {{assign var="_object_class" value="CModelCodage"}}
        {{mb_include module=cabinet template=inc_codage_ngap object=$subject}}
      </div>
    </div>
  {{/if}}

  {{if 'lpp'|module_active && "lpp General cotation_lpp"|gconf}}
    <div id="lpp" style="display: none;">
      {{mb_include module=lpp template=inc_codage_lpp codable=$subject}}
    </div>
  {{/if}}

  {{if @$modules.tarmed->_can->read && 'tarmed CCodeTarmed use_cotation_tarmed'|gconf}}
    {{mb_script module=tarmed script=actes ajax=true}}
    <script>
      Main.add(function() {
        ActesTarmed.loadList('{{$subject->_id}}', '{{$subject->_class}}', '{{$subject->praticien_id}}');
        ActesCaisse.loadList('{{$subject->_id}}', '{{$subject->_class}}', '{{$subject->praticien_id}}');
      });
    </script>
    <div id="tarmed_tab" style="display:none">
      <div id="listActesTarmed"></div>
    </div>
    <div id="caisse_tab" style="display:none">
      <div id="listActesCaisse"></div>
    </div>
  {{/if}}
</div>

<div id="tarif_actions" style="text-align: center;">
  <form name="editModelCodage" action="?" method="post" onsubmit="return onSubmitFormAjax(this, Control.Modal.close.curry());">
    <input type="hidden" name="m" value="cabinet">
    <input type="hidden" name="dosql" value="">
    <input type="hidden" name="del" value="1">

    {{mb_class object=$subject}}
    {{mb_key   object=$subject}}

    <button type="button" class="tick singleclick" onclick="ApplyCodage();">{{tr}}Apply{{/tr}}</button>
    <button type="button" class="cancel" onclick="this.form.onsubmit();">{{tr}}Cancel{{/tr}}</button>
  </form>
</div>
