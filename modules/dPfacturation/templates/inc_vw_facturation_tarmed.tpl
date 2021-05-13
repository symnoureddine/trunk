{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if !@$modules.tarmed->_can->read || !'tarmed CCodeTarmed use_cotation_tarmed'|gconf}}
  {{mb_return}}
{{/if}}
{{mb_script module=patients    script=correspondant  ajax="true"}}
{{mb_script module=facturation script=journal_envoi_xml  register="true"}}

{{assign var=use_relances value="dPfacturation CRelance use_relances"|gconf}}
{{if $facture->cloture && (!$facture->annule || $facture->extourne)}}
  <tr>
    <td colspan="8">
      {{if !$facture->annule && ($facture->bill_date_printed || $facture->justif_date_printed)}}
        <div class="info">
          {{if $facture->bill_date_printed}}
            {{mb_include module=facturation template=inc_printed_bill facture=$facture field=bill_date_printed tip_left=true
                         deny_callback="(Control.Modal.stack.length ? Control.Modal.refresh : Reglement.reload)"}}
          {{/if}}
          {{if $facture->bill_date_printed && $facture->justif_date_printed}} {{tr}}and{{/tr}} {{/if}}
          {{if $facture->justif_date_printed}}
            {{mb_include module=facturation template=inc_printed_bill facture=$facture field=justif_date_printed tip_left=true
                         deny_callback="(Control.Modal.stack.length ? Control.Modal.refresh : Reglement.reload)"}}
          {{/if}}
        </div>
      {{/if}}
      <button class="printPDF" style="float:left;"
              {{if $facture->_ref_echeances|@count > 0}}
                onclick="Control.Tabs.activateTab('echeances-{{$facture->_guid}}')"
              {{else}}
                onclick="Facture.printFacture('{{$facture->_id}}', '{{$facture->_class}}', 'bvr');"
              {{/if}}
      >
        {{tr}}CEditPdf.edit_bvr{{/tr}}
      </button>
      <button class="print" style="float:left;"
              onclick="Facture.printFacture('{{$facture->_id}}', '{{$facture->_class}}', 'justificatif');">
        {{tr}}CEditPdf.edit_justif{{/tr}}
      </button>

      {{if $facture->_ref_reglements|@count}}
        {{if $facture->isTiersSoldant() && "tarmed coefficient pt_maladie"|conf:$facture->_host_config}}
          <button class="printPDF" style="float:left;" onclick="Facture.printFacture('{{$facture->_id}}', '{{$facture->_class}}', 'bvr_TS');">{{tr}}CEditPdf.facture_patient{{/tr}}</button>
        {{/if}}
      {{/if}}
      {{if $facture->_ref_echeances|@count > 0}}
        <button class="print" style="float:left;" onclick="Facture.printFacture('{{$facture->_id}}', '{{$facture->_class}}', 'bvr_justif');"
                title="{{tr}}CEditPdf.edit_bvr{{/tr}} {{tr}}and{{/tr}} {{tr}}CEditPdf.edit_justif{{/tr}}">
          {{tr}}CFactureEtablissement.print{{/tr}}
        </button>
      {{/if}}

      {{if !$facture->annule && $facture->_ref_envois_cdm|@count}}
        <button class="search" style="float:left;" onclick="Facture.envoisCDM('{{$facture->_guid}}');" title="{{tr}}CEnvoiCDM{{/tr}}">
          {{tr}}CEnvoiCDM-court{{/tr}}
        </button>
      {{/if}}

      {{if !$facture->annule && $facture->statut_envoi !== 'non_envoye'}}
        <div class="small-{{if $facture->statut_envoi == 'envoye'}}warning{{else}}error{{/if}}" style="float:left;margin: 0;">
          {{tr}}Facture.envoi_xml.{{if $facture->statut_envoi == 'envoye'}}facture{{else}}echec{{/if}}{{/tr}}
          {{if $facture->statut_envoi == 'echec'}}
            <button type="button" class="list notext" onclick="Facture.seeEltsMiss('{{$facture->_guid}}');">
              {{tr}}Facture.elts_miss{{/tr}}
            </button>
          {{/if}}
          <form name="facture_renvoi" method="post" action="">
            {{mb_class object=$facture}}
            {{mb_key   object=$facture}}
            <input type="hidden" name="facture_class" value="{{$facture->_class}}" />
            <input type="hidden" name="statut_envoi" value="non_envoye"/>
            <input type="hidden" name="msg_error_xml" value=""/>
            <button type="button" class="change" onclick="Facture.modifCloture(this.form);">
              {{tr}}Facture.envoi_xml.again{{/tr}}
            </button>
          </form>
        </div>
      {{elseif !$facture->annule && $facture->cloture && $facture->statut_envoi == 'non_envoye' &&
        $app->user_prefs.send_bill_unity && $facture->envoi_xml}}
        <div id="check_bill" style="display:none;" data-count="" data-source_envoi=""></div>
        <button class="singleclick send" type="button"
                onclick="Facture.sendBill('{{$facture->_id}}', '{{$facture->_class}}', '{{$facture->praticien_id}}');">
          {{tr}}CFacture-action-send{{/tr}}
        </button>
      {{/if}}
      {{if !$facture->annule && $facture->_ref_journaux_envoi_xml|@count > 0}}
        <button type="button" class="list" style="float:left"
                onclick="Control.Modal.open('journaux_envoi_xml_{{$facture->_guid}}', {width: 500})">
          {{tr}}CJournalEnvoiXml{{/tr}}
        </button>
        <div id="journaux_envoi_xml_{{$facture->_guid}}" class="modal-wrapper modal popup" style="display:none">
          <table class="main tbl">
            <thead>
            <tr>
              <th class="title" colspan="5">
                {{tr}}CJournalEnvoiXml|pl{{/tr}}
                <button type="button" class="cancel notext" onclick="Control.Modal.close();" style="float:right">
                  {{tr}}Close{{/tr}}
                </button>
              </th>
            </tr>
            <tr>
              <th class="narrow">{{mb_label class=CJournalEnvoiXml field=date_envoi}}</th>
              <th>{{mb_label class=CJournalEnvoiXml field=user_id}}</th>
              <th class="narrow">{{mb_label class=CJournalEnvoiXml field=error}}</th>
              <th>{{mb_label class=CJournalEnvoiXml field=statut}}</th>
              <th class="narrow"></th>
            </tr>
            </thead>
            {{foreach from=$facture->_ref_journaux_envoi_xml item=_journal}}
              <tr>
                <td>{{mb_value object=$_journal field=date_envoi}}</td>
                <td>
                  <span onmouseover="ObjectTooltip.createEx(this, 'CMediusers-{{$_journal->user_id}}}');">
                    {{mb_value object=$_journal field=user_id}}
                  </span>
                </td>
                <td>{{mb_value object=$_journal field=error}}</td>
                <td title="{{$_journal->statut}}">{{$_journal->statut|truncate:50:"...":true}}</td>
                <td>
                  <button type="button" class="search notext" onclick="JournalEnvoiXml.open({{$_journal->_id}})">
                    {{tr}}Show{{/tr}}
                  </button>
                </td>
              </tr>
            {{/foreach}}
          </table>
        </div>
      {{/if}}
      {{assign var=name value='Ox\Mediboard\Facturation\CFacture'|static:_file_name}}
      {{if (array_key_exists($name, $facture->_ref_named_files) && $facture->_ref_named_files.$name->file_id)}}
        {{assign var=file value=$facture->_ref_named_files.$name}}
        {{assign var=title value=$app->tr('Facture.envoi_xml.facture')}}
        {{thumblink document=$file title="$title"}}
          <button type="button" class="multiline" style="float: left;">{{tr}}Facture.envoi_xml.facture{{/tr}}</button>
        {{/thumblink}}
      {{/if}}

      {{if !$facture->annule && $facture->_is_relancable && $use_relances}}
        <form name="facture_relance" method="post" action="" onsubmit="return Relance.create(this);">
          {{mb_class object=$facture->_ref_last_relance}}
          <input type="hidden" name="relance_id" value=""/>
          <input type="hidden" name="object_id" value="{{$facture->_id}}"/>
          <input type="hidden" name="object_class" value="{{$facture->_class}}"/>
          <button class="add" type="button" onclick="this.form.onsubmit();">
            {{tr}}CFacture-action-create-relance{{/tr}}
          </button>
        </form>
      {{/if}}
      {{if !$facture->annule && !$facture->_ref_patient->avs &&
        ($facture->statut_pro == "invalide" || $facture->statut_pro == "militaire")}}
        <div class="small-error" style="display:inline;margin:0;">{{tr}}CPatient.avs.not_present{{/tr}}</div>
      {{/if}}
      {{if !$facture->annule && $facture->_reglements_total == 0 && !$facture->_ref_echeances|@count}}
        <form name="facture_extourne" method="post" action="?" style="float:right;" >
          {{mb_class object=$facture}}
          {{mb_key   object=$facture}}
          <input type="hidden" name="facture_class" value="{{$facture->_class}}"/>
          <input type="hidden" name="_duplicate" value="1"/>
          <input type="hidden" name="annule" value="0"/>
          <button type="button" class="duplicate" onclick="Facture.extourne(this.form);">
            {{tr}}Facture.extourner{{/tr}}
          </button>
          {{if $conf.ref_pays == 2 && "dPfacturation `$facture->_class` can_deny_invoice"|gconf}}
            <button type="button" class="cancel" onclick="Facture.annule(this.form)">
              {{tr}}Cancel{{/tr}}
            </button>
          {{/if}}
        </form>
      {{/if}}
    </td>
  </tr>
{{/if}}

<tr>
  <td colspan="8" style="padding: 0;">
    <form name="type_facture" method="post" action="">
      {{mb_class object=$facture}}
      {{mb_key   object=$facture}}
      {{assign var=tarmed_field_style value=false}}
      {{if $conf.ref_pays == 2 && !$facture->no_relance && !$facture->cession_creance && !$facture->envoi_xml &&
        (($facture->_class == "CFactureEtablissement" && !$facture->dialyse) || $facture->_class == "CFactureCabinet") &&
        !$facture->coeff_id && !$facture->npq}}
        {{assign var=tarmed_field_style value="display: none;"}}
      {{/if}}

      <input type="hidden" name="facture_class" value="{{$facture->_class}}" />
      <input type="hidden" name="not_load_banque" value="{{if isset($factures|smarty:nodefaults) && count($factures)}}0{{else}}1{{/if}}" />
      <table class="main tbl">
        <tr>
          <td class="narrow">{{mb_label object=$facture field=type_facture}}</td>
          <td>{{mb_field object=$facture field=type_facture onchange="Facture.modifCloture(this.form);" readonly=$facture->cloture}}</td>
          <td class="narrow">{{mb_label object=$facture field=statut_pro}}</td>
          <td colspan="{{if $use_relances}}1{{else}}3{{/if}}">
            {{mb_field object=$facture field=statut_pro emptyLabel="Choisir un status" onchange="Facture.cut(this.form);"
              readonly=$facture->cloture}}
          </td>
          {{if $use_relances}}
            <td class="narrow factu-tarmed-toggle" style="{{$tarmed_field_style}}">{{mb_label object=$facture field=no_relance}}</td>
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">
              {{assign var=readonly_relancable value=0}}
              {{if $facture->annule || $facture->_du_restant < 0.01}}
                {{assign var=readonly_relancable value=1}}
              {{/if}}
              {{mb_field object=$facture field=no_relance onchange="Facture.cut(this.form);" readonly=$readonly_relancable}}
            </td>
          {{/if}}
          <td class="factu-tarmed-toggle" style="width:400px;{{$tarmed_field_style}}"> {{mb_label object=$facture field=cession_creance}}</td>
          <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">
            {{mb_field object=$facture field=cession_creance onchange="Facture.saveNoRefresh(this.form);" readonly=$facture->cloture}}
          </td>

          {{if $facture->_is_ambu && 'dPfacturation Other select_diagnostic_ambu'|gconf}}
            <td class="narrow factu-tarmed-toggle" style="{{$tarmed_field_style}}"> {{mb_label object=$facture field=diagnostic_id}}</td>
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">
              {{if $facture->cloture}}
                {{mb_value object=$facture field=diagnostic_id}}
                {{if !$facture->diagnostic_id}}
                  <div class="small-warning">{{tr}}CDiagnostic.none{{/tr}}</div>
                {{/if}}
              {{else}}
                <script>
                  Main.add(function () {
                    var form = getForm("type_facture");
                    var url = new Url("tarmed", "ajax_diagnostic_autocomplete");
                    url.autoComplete(form.diagnostic_view, null, {
                      minChars: 0,
                      dropdown: true,
                      updateElement: function(selected) {
                        $V(form.diagnostic_id, selected.get('id'));
                        $V(form.diagnostic_view, selected.select(".view")[0].innerHTML.stripTags(), false);
                      }
                    });
                  });
                </script>
                {{mb_field object=$facture field=diagnostic_id onchange="Facture.saveNoRefresh(this.form);" hidden=true}}
                <input type="text" name="diagnostic_view" value="{{mb_value object=$facture field=diagnostic_id}}"
                       {{if $facture->cloture}}readonly="readonly"{{/if}}/>
                <button type="button" class="cancel notext" title="{{tr}}Clear{{/tr}}"
                        onclick="$V(this.form.diagnostic_id, '');$V(this.form.diagnostic_view, '');"></button>
              {{/if}}
            </td>
          {{/if}}

          {{if $conf.ref_pays == 2 }}
            {{assign var=comptes_prat value=$facture->_ref_praticien->loadRefsCompteCh()}}
            <td class="factu-tarmed-toggle" style="{{if !$tarmed_field_style}}display: none{{/if}}">{{mb_label object=$facture field=compte_ch_id}}</td>
            <td class="factu-tarmed-toggle" style="{{if !$tarmed_field_style}}display: none{{/if}}">
              {{if $comptes_prat|@count > 1}}
                <select onchange="$V(this.form.compte_ch_id, $V(this)); Facture.saveNoRefresh(this.form);"
                        {{if $facture->cloture}}disabled{{/if}}
                        style="width: 139px;">
                  {{if !$facture->compte_ch_id}}
                    <option value="" selected="selected">&mdash; {{tr}}Choose{{/tr}}</option>
                  {{/if}}
                  {{foreach from=$comptes_prat item=_compte_ch}}
                    <option value="{{$_compte_ch->_id}}" {{if $facture->compte_ch_id == $_compte_ch->_id}}selected="selected"{{/if}}>
                      {{$_compte_ch->_view}}
                    </option>
                  {{/foreach}}
                </select>
              {{else}}
                {{mb_value object=$facture field=compte_ch_id}}
              {{/if}}
            </td>
            <td class="narrow factu-tarmed-toggle" style="{{if !$tarmed_field_style}}display: none{{/if}}" colspan="2">
              <button type="button" class="down notext" title="{{tr}}CFacture-Show more{{/tr}}"
                      onclick="Facture.toggleTarmedHideFields(this.form)"></button>
            </td>
          {{/if}}
        </tr>

        <tr>
          <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">{{mb_label object=$facture field=envoi_xml}}</td>
          <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">
            {{mb_field object=$facture field=envoi_xml readonly=$facture->cloture
              onchange="Facture.toggleDelaiEnvoiXml(this);Facture.saveNoRefresh(this.form);"}}
            <button type="button" class="me-tertiary {{if !$facture->delai_envoi_xml}}notext{{/if}} clock"
                    {{if !$facture->envoi_xml}}style="display: none;"{{/if}}
                    onclick="this.up().down('.facture-delai-envoi-xml').toggle();"
                    title="{{if $facture->delai_envoi_xml}}{{tr}}CFactureCabinet-delai_envoi_xml-desc{{/tr}}{{else}}{{tr}}CFacture.Modify send delay{{/tr}}{{/if}}"
                    {{if $facture->cloture}}disabled="disabled"{{/if}}>
              {{if $facture->delai_envoi_xml}}
                {{$facture->delai_envoi_xml}} {{tr}}days{{/tr}}
              {{/if}}
            </button>
            <div class="facture-delai-envoi-xml" style="display: none;">
              {{assign var=conf_delay value='dPfacturation CEditBill delay_send_xml'|gconf}}
              {{if $conf_delay}}
                {{assign var=conf_delay value=$conf_delay*30}}
              {{/if}}
              {{me_form_field nb_cells=0 mb_object=$facture mb_field=delai_envoi_xml field_class="me-margin-top-8"}}
                <input type="num" name="_delai_envoi_xml" class="num default|{{$conf_delay}}" size="4"
                       onchange="$V(this.form.delai_envoi_xml, $V(this));Facture.modifCloture(this.form);"
                       value="{{$facture->delai_envoi_xml}}" placeholder="{{$conf_delay}}"/>
                {{mb_field object=$facture field=delai_envoi_xml hidden=true}}
              {{/me_form_field}}
            </div>
          </td>
          {{if "dPfacturation Other use_coeff_bill"|gconf}}
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">{{mb_label object=$facture field=coeff_id}}</td>
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">
              <select name="coeff_id" onchange="Facture.modifCloture(this.form);" {{if $facture->cloture}}disabled{{/if}}>
                <option value="" {{if !$facture->coeff_id}}selected="selected" {{/if}}>&mdash; {{tr}}common-action-Choose{{/tr}}</option>
                {{foreach from=$facture->_ref_coefficients item=_coeff}}
                  <option value="{{$_coeff->_id}}" {{if $facture->coeff_id == $_coeff->_id}} selected="selected" {{/if}}>
                    {{$_coeff->nom}}
                  </option>
                {{/foreach}}
              </select>
            </td>
          {{/if}}

          {{if $conf.ref_pays == 2}}
            {{assign var=comptes_prat value=$facture->_ref_praticien->loadRefsCompteCh()}}
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">{{mb_label object=$facture field=compte_ch_id}}</td>
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">
              {{if $comptes_prat|@count > 1}}
                <select name="compte_ch_id"
                        onchange="$V(this.form.compte_ch_id, $V(this)); Facture.saveNoRefresh(this.form);" {{if $facture->cloture}}disabled{{/if}}
                        style="width: 139px;">
                  <option value="" {{if !$facture->compte_ch_id}}selected="selected" {{/if}}>&mdash; {{tr}}Choose{{/tr}}</option>
                  {{foreach from=$comptes_prat item=_compte_ch}}
                    <option value="{{$_compte_ch->_id}}" {{if $facture->compte_ch_id == $_compte_ch->_id}}selected="selected"{{/if}}>
                      {{$_compte_ch->_view}}
                    </option>
                  {{/foreach}}
                </select>
              {{else}}
                {{mb_value object=$facture field=compte_ch_id}}
              {{/if}}
            </td>
          {{/if}}

          {{if !"dPfacturation Other use_coeff_bill"|gconf}}
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}" colspan="2"></td>
          {{/if}}
          {{if $conf.ref_pays != 2}}
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}" colspan="2"></td>
          {{/if}}

          {{if $facture->_class == "CFactureCabinet"}}
            <td class="narrow factu-tarmed-toggle" style="{{$tarmed_field_style}}">{{mb_label object=$facture field=npq}}</td>
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">{{mb_field object=$facture field=npq onchange="Facture.saveNoRefresh(this.form);" readonly=$facture->cloture}}</td>
          {{elseif $facture->_class == "CFactureEtablissement"}}
            <td class="narrow factu-tarmed-toggle" style="{{$tarmed_field_style}}">{{mb_label object=$facture field=dialyse}}</td>
            <td class="factu-tarmed-toggle" style="{{$tarmed_field_style}}">{{mb_field object=$facture field=dialyse onchange="Facture.modifCloture(this.form);" readonly=$facture->cloture}}</td>
          {{/if}}
          {{if $facture->_is_ambu && 'dPfacturation Other select_diagnostic_ambu'|gconf}}
            <td class="factu-tarmed-toggle" colspan="2"></td>
          {{/if}}
        </tr>
      </table>
    </form>
  </td>
</tr>
{{if $facture->type_facture != "esthetique"}}
  <tr>
    <td colspan="{{if $facture->_class == 'CFactureCabinet' || !$facture->dialyse}}2{{elseif $facture->dialyse}}3{{/if}}"
        id="refresh-assurance" style="padding: 0;">
      {{mb_include module=facturation template="inc_vw_assurances"}}
    </td>
    <td colspan="{{if $facture->_class == 'CFactureCabinet' || !$facture->dialyse}}5{{elseif $facture->dialyse}}4{{/if}}"
        style="padding: 0;">
      {{if $facture->_class == "CFactureEtablissement" && $facture->_ref_sejours|@count}}
        <button type="button" class="new" onclick="Facture.gestionFacture('{{$facture->_ref_last_sejour->_id}}')" style="float: right">
          {{tr}}module-dPfacturation-long{{/tr}}
        </button>
      {{/if}}
      {{if $facture->rques_assurance_maladie}}
        <div class="info"><b>{{tr}}CFacture.remarque{{/tr}}</b>{{mb_value object=$facture field=rques_assurance_maladie}}</div>
      {{/if}}
      <div style="width:400px;">
        <form name="facture_remarque" method="post" action="">
          {{mb_class object=$facture}}
          {{mb_key   object=$facture}}
          <input type="hidden" name="facture_class" value="{{$facture->_class}}" />
          {{mb_label object=$facture field=remarque}}
          {{mb_field object=$facture field=remarque onchange="return onSubmitFormAjax(this.form);" readonly=$facture->cloture}}
        </form>
      </div>
    </td>
  </tr>
{{/if}}

{{if $facture->type_facture == "accident" || ($facture->type_facture == "maladie" && $facture->statut_pro == "invalide")}}
  <tr>
    <td colspan="2">
      <form name="ref_accident-{{$facture->_guid}}" method="post" action="" onsubmit="return onSubmitFormAjax(this);" style="max-width:100px;">
        {{mb_class object=$facture}}
        {{mb_key   object=$facture}}
        {{assign var=facture_guid value=$facture->_guid}}

        <b>{{mb_label object=$facture field=date_cas}}:</b>
        {{if $facture->cloture}}
          {{mb_value object=$facture field="date_cas"}}
        {{else}}
          {{mb_field object=$facture field="date_cas" onchange="return onSubmitFormAjax(this.form);" form="ref_accident-$facture_guid" register=true}}
        {{/if}}
        <br/>
        <b>{{mb_label object=$facture field=ref_accident}}:</b>
        {{if $facture->cloture}}
          {{mb_value object=$facture field="ref_accident"}}
        {{else}}
          {{mb_field object=$facture field="ref_accident" onchange="return onSubmitFormAjax(this.form);"}}
        {{/if}}
      </form>
    </td>
    <td colspan="9"></td>
  </tr>
{{/if}}

<tr>
  <th class="category">{{tr}}Date{{/tr}}</th>
  <th class="category">{{tr}}CGroups-code{{/tr}}</th>
  <th class="category">{{tr}}CPrescription-libelle{{/tr}}</th>
  <th class="category">{{tr}}common-Cost{{/tr}}</th>
  <th class="category">{{tr}}CFactureItem-quantite-court{{/tr}}</th>
  <th class="category">{{tr}}CFactureItem-coeff-court{{/tr}}</th>
  <th class="category">{{tr}}CFacture-montant{{/tr}}</th>
</tr>

{{if $facture->_ref_items|@count}}
  {{foreach from=$facture->_ref_items item=item}}
    <tr>
      <td style="text-align:center;width:100px;">
        {{if $facture->_ref_last_sejour->_id}}
        <span onmouseover="ObjectTooltip.createEx(this, '{{$facture->_ref_last_sejour->_guid}}')">
          {{else}}
            <span onmouseover="ObjectTooltip.createEx(this, '{{$facture->_ref_last_consult->_guid}}')">
          {{/if}}
          {{mb_value object=$item field="date"}}
        </span>
      </td>
      <td class="acte-{{$item->type}}" style="width:140px;">
        <span onmouseover="ObjectTooltip.createEx(this, '{{$item->_guid}}')">
          {{mb_value object=$item field="code"}}
        </span>
      </td>
      <td style="white-space: pre-line;" class="compact">{{mb_value object=$item field="libelle"}}</td>
      <td style="text-align:right;">
        {{$item->montant_base|string_format:"%0.2f"}} {{tr}}CFactureItem.pts{{/tr}}
      </td>
      <td style="text-align:right;">{{mb_value object=$item field="quantite"}}</td>
      <td style="text-align:right;">{{$item->coeff|string_format:"%0.4f"|rtrim:'0'}}</td>
      <td style="text-align:right;">{{$item->montant_base*$item->coeff*$item->quantite|string_format:"%0.2f"|currency}}</td>
    </tr>
  {{/foreach}}
{{else}}
  {{foreach from=$facture->_ref_actes_tarmed item=_acte_tarmed}}
    {{mb_include module=dPfacturation template="inc_line_tarmed"}}
  {{/foreach}}
  {{foreach from=$facture->_ref_actes_caisse item=_acte_caisse}}
    {{mb_include module=dPfacturation template="inc_line_caisse"}}
  {{/foreach}}
{{/if}}
<tbody class="hoverable">
  <tr>
    <td colspan="4"></td>
    <td colspan="2"><b>{{mb_label object=$facture field="remise"}}</b></td>
    <td style="text-align: right;">
      <form name="modif_remise" method="post" onsubmit="Facture.modifCloture(this.form);">
        {{mb_class object=$facture}}
        {{mb_key   object=$facture}}
        <input type="hidden" name="facture_class" value="{{$facture->_class}}" />
        <input type="hidden" name="patient_id" value="{{$facture->patient_id}}" />
        <input type="hidden" name="not_load_banque" value="{{if isset($factures|smarty:nodefaults) && count($factures)}}0{{else}}1{{/if}}" />

        {{if $facture->cloture}}
          {{mb_value object=$facture field=remise}}
        {{else}}
          {{mb_field object=$facture field=remise onchange="Facture.modifCloture(this.form);"
                     onkeypress="if(event.charCode === 13) { event.preventDefault(); event.stopPropagation(); this.onchange(); }"}}
        {{/if}}

        <br/>{{tr}}Facture.remise.resume{{/tr}}
        {{if $facture->_montant_sans_remise!=0 && $facture->remise}}
          <strong>{{math equation="(y/x)*100" x=$facture->_montant_sans_remise y=$facture->remise format="%.2f"}} %</strong>
        {{else}}
          <strong>0 %</strong>
        {{/if}}
      </form>
    </td>
  </tr>

  {{assign var="nb_montants" value=$facture->_montant_factures|@count}}
  {{foreach from=$facture->_montant_factures item=_montant key=key name=montants}}
    <tr>
      {{if $smarty.foreach.montants.first}}
      <td colspan="4" rowspan="{{$nb_montants+2}}"></td>
      {{/if}}
      <td colspan="2">{{tr}}CFacture.montant{{if $nb_montants > 1}}_num{{/if}}{{/tr}} {{if $nb_montants > 1}}{{$key+1}}{{/if}}</td>
      <td style="text-align:right;">{{$_montant|string_format:"%0.2f"|currency}}</td>
    </tr>
  {{/foreach}}
  {{if $facture->_ref_echeances|@count > 0}}
    <tr>
      <td colspan="2">{{tr}}CFactureEtablissement-_montant_echeance{{/tr}}</td>
      <td style="text-align:right;">{{mb_value object=$facture field="_montant_echeance"}} (+{{$facture->_interest_echeance}}%)</td>
    </tr>
    <tr>
      <td colspan="2"><b>{{tr}}CFactureEtablissement-montant_total{{/tr}}</b></td>
      <td style="text-align:right;"><b>{{mb_value object=$facture field="_montant_total_echeance"}}</b></td>
    </tr>
  {{else}}
    <tr>
      <td colspan="2"><b>{{tr}}CFactureEtablissement-montant_total{{/tr}}</b></td>
      <td style="text-align:right;"><b>{{mb_value object=$facture field="_montant_avec_remise"}}</b></td>
    </tr>
  {{/if}}
</tbody>

{{assign var="classe" value=$facture->_class}}
{{if !"dPfacturation $classe use_auto_cloture"|gconf && !$facture->annule && !$facture->definitive
    && (!isset($show_button|smarty:nodefaults) || $show_button) && $facture->_ref_echeances|@count == 0}}
  <tr>
    <td colspan="7">
      <form name="change_type_facture" method="post">
        {{mb_class object=$facture}}
        {{mb_key   object=$facture}}
        <input type="hidden" name="facture_class" value="{{$facture->_class}}" />
        <input type="hidden" name="cloture" value="{{if !$facture->cloture}}{{$dnow}}{{/if}}" />
        <input type="hidden" name="not_load_banque" value="{{if isset($factures|smarty:nodefaults) && count($factures)}}0{{else}}1{{/if}}" />
        {{if !$facture->cloture}}
          <button class="submit" type="button" onclick="Facture.modifCloture(this.form);"
          {{if "dPfacturation Other use_strict_cloture"|gconf && $facture->type_facture != "esthetique" &&
            (!$facture->statut_pro || (!$facture->assurance_maladie && !$facture->assurance_accident))}}
          disabled
          {{/if}}
          >{{tr}}CFactureCabinet-action-close-invoice{{/tr}}</button>
        {{elseif (!$facture->_reglements_total_patient || "dPfacturation CReglement add_pay_not_close"|gconf)
          && $facture->statut_envoi !== "envoye"}}
          <button class="submit" type="button" onclick="
            {{if $facture->bill_date_printed}}
              if (confirm($T('CFacture-warning the invoice has already been sent, confirm reopen'))) {
            {{/if}}
            Facture.modifCloture(this.form);
            {{if $facture->bill_date_printed}}
              }
            {{/if}}" >
            {{tr}}CFactureCabinet-action-reopen-invoice{{/tr}}
          </button>
          {{tr}}CFactureCabinet-facture.cloture_of|f{{/tr}} {{$facture->cloture|date_format:$conf.date}}
        {{/if}}
      </form>
    </td>
  </tr>
{{/if}}