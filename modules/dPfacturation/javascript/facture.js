/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

window.Facture = {
  evenement_guid: null,
  evenement_id: null,
  user_id: null,
  saveNoRefresh: function(form) {
    return onSubmitFormAjax(form);
  },
  reload: function(patient_id, consult_id ,not_load_banque, facture_id, object_class) {
    if (!$('load_facture')) {
      Facture.reloadFactureModal(facture_id, object_class);
    }
    else {
      var url = new Url('facturation', 'ajax_view_facture');
      url.addParam('patient_id'      , patient_id);
      url.addParam('consult_id'      , consult_id);
      url.addParam('object_class'    , object_class);
      url.addParam('not_load_banque' , not_load_banque);
      url.addParam('facture_id'      , facture_id);
      url.requestUpdate('load_facture');
    }
  },
  modifCloture: function(form) {
    return onSubmitFormAjax(form, {
      onComplete : this.callbackModif.curry(form)
    });
  },
  callbackModif: function(form, factureId, factureClass) {
    if (!$('load_facture')) {
      // Reload the consultation's facture container
      Control.Modal.refresh();
    }
    else if ($('facturation')) {
      Reglement.reload();
    }
    else {
      factureId = factureId ? factureId : $V(form.facture_id);
      factureClass = factureClass ? factureClass : $V(form.facture_class);
      // Refresh the facture modal
      Facture.reloadFactureModal(factureId, factureClass, $('load_facture') ? 'load_facture' : null);
    }
  },
  extourne: function(form) {
    this.annule(form, 1);
  },
  annule: function(form, duplicate) {
    if (!confirm($T('CFacture-confirm ' + (duplicate ? 'extourne' : 'annule')))) {
      return false;
    }
    $V(form._duplicate, duplicate ? 1 : 0);
    $V(form.annule, duplicate ? 0 : 1);
    return onSubmitFormAjax(form, {
      onComplete : function() {
        if ($('a_reglements_consult')) {
          Reglement.reload();
        }
        else if ($('a_reglements_evt')) {
          $('a_reglements_evt').onmousedown();
        }
        else {
          Facture.reloadFactureModal($V(form.facture_id), $V(form.facture_class), $('load_facture') ? 'load_facture' : null);
        }
      }
    });
  },
  reloadReglement: function(facture_id, facture_class) {
    var url = new Url('facturation', 'ajax_refresh_reglement');
    url.addParam('facture_id'    , facture_id);
    url.addParam('facture_class' , facture_class);
    url.requestUpdate('reglements_facture');
    if (!$('load_facture')) {
      Facture.reloadFactureModal(facture_id, facture_class);
    }
  },
  cut: function(form) {
    onSubmitFormAjax(form, {
      onComplete : function() {
        if (!$('load_facture')) {
          Facture.reloadFactureModal($V(form.facture_id), $V(form.facture_class));
        }
        else {
          var url = new Url('facturation', 'ajax_view_facture');
          url.addElement(form.facture_id);
          url.addParam('object_class'  , $V(form.facture_class));
          url.requestUpdate("load_facture");
        }
      }
    });
  },
  edit: function(facture_id, facture_class, show_button, refreshAfterClose) {
    show_button = show_button || 1;
    var url = new Url('facturation', 'ajax_view_facture');
    url.addParam('facture_id'    , facture_id);
    url.addParam("object_class", facture_class);
    url.addParam("show_button", show_button);
    url.requestModal('90%', '90%');
    if (refreshAfterClose) {
      url.modalObject.observe("afterClose", Control.Modal.refresh);
    }
  },

  printFacture: function(facture_id, facture_class, type_pdf) {
    var url = new Url('facturation', 'ajax_edit_bvr');
    url.addParam('facture_class', facture_class);
    url.addParam('facture_id'   , facture_id);
    url.addParam('type_pdf'     , type_pdf);
    url.addParam('suppressHeaders', '1');
    var callback = Control.Modal.stack.length ? Control.Modal.refresh : (Reglement ? Reglement.reload : Prototype.emptyFunction);

    url.popup(1000, 600);
    this.checkDocClose(url, getForm('change_type_facture'), facture_class, facture_id, type_pdf);
  },
  checkDocClose: function(url, form, facture_class, facture_id, type_pdf) {
    if (url.oWindow.closed) {
      if (confirm($T('CFacture.confirm_bill_print_update'))) {
        var field = type_pdf === "justificatif" ? "justif_" : "bill_";
        var updateForm = DOM.form(
          {
            method: 'post',
            action: '',
            name: 'ajax_save_facture'
          },
          DOM.input({value: facture_id,    name: 'facture_class'}),
          DOM.input({value: facture_class, name: '@class'}),
          DOM.input({value: facture_id,    name: 'facture_id'}),
          DOM.input({value: 'now',         name: field + 'date_printed'}),
          DOM.input({value: User.id,       name: field + 'user_printed'})
        );
        onSubmitFormAjax(updateForm , this.callbackModif.bind(this).curry(form));
      }
      return;
    }
    setTimeout(this.checkDocClose.bind(this).curry(url, form, facture_class, facture_id, type_pdf), 250);
  },

  printGestion: function(type_pdf, facture_class, form, no_printed) {
    if(!$V(form.chir)) {
      alert($T('Compta.choose_prat'));
      return false;
    }
    var url = new Url('facturation', 'ajax_edit_bvr');
    url.addParam('facture_class'   , facture_class);
    url.addParam('type_pdf'        , type_pdf);
    url.addElement(form._date_min);
    url.addElement(form._date_max);
    url.addParam('prat_id'         , form.chir.value);
    url.addParam('no_printed'      , no_printed);
    url.addParam('suppressHeaders' , '1');
    url.popup(1000, 600);
  },

  editEvt: function(evenement_guid) {
    var url = new Url("facturation", "vw_edit_facture_evt");
    url.addParam("evenement_guid", evenement_guid);
    url.requestModal('90%', '90%');
  },

  reloadEvt: function(evenement_guid, reload_acts, callback) {
    if (!evenement_guid) {
      evenement_guid = Facture.evenement_guid;
    }
    var url = new Url("facturation", "vw_facture_evt");
    url.addParam("evenement_guid", evenement_guid);
    url.requestUpdate('reglement_evt', callback);

    // Rafraichissement des actes CCAM et NGAP
    if (reload_acts && Preferences.ccam_consultation == "1" && (Preferences.MODCONSULT == "1" || Preferences.UISTYLE == "tamm")){
      ActesCCAM.refreshList(Facture.evenement_id, Facture.user_id);
      if (window.ActesNGAP) {
        ActesNGAP.refreshList();
      }
      if ($('fraisdivers')) {
        refreshFraisDivers();
      }

      if (window.ActesTarmed){
        ActesTarmed.refreshList();
        ActesCaisse.refreshList();
      }

      if (!window.ActesNGAP && !window.ActesTarmed && $('Actes')) {
        loadActes();
      }
    }
  },

  submitEvt: function(form, evenement_guid, reload_acts, callback) {
    onSubmitFormAjax(form, {
      onComplete: function () {
        Facture.reloadEvt(evenement_guid, reload_acts, callback);
      }
    }
    );
  },
  envoisCDM: function(facture_guid) {
    var url = new Url("facturation" , "vw_envois_cdm");
    url.addParam("view_list"   , 1);
    url.addParam("facture_guid", facture_guid);
    url.requestModal('90%', '90%');
  },
  seeEltsMiss: function(facture_guid) {
    var url = new Url("facturation" , "vw_facture_elt_miss");
    url.addParam("facture_guid", facture_guid);
    url.requestModal('50%', '70%');
  },
  updateEtatSearch: function() {
    var form = getForm("choice-facture");
    if ($V(form.type_date_search) == "cloture") {
      form.search_easy[2].disabled = "disabled";
    }
    else {
      form.search_easy[2].disabled = "";
    }
  },
  viewPatient: function() {
    var form = getForm("choice-facture");
    if (form.patient_id.value) {
      var url = new Url('patients', 'vw_edit_patients', 'tab');
      url.addElement(form.patient_id);
      url.redirect();
    }
  },
  changePage: function(page) {
    var form = getForm("choice-facture");
    var url = new Url("facturation" , "ajax_list_factures");
    url.addParam('facture_class', $V(form.facture_class));
    url.addParam('page'         , page);
    url.requestUpdate("liste_factures");
  },
  showLegend: function(facture_class) {
    var url = new Url('facturation', 'vw_legende');
    url.addParam('classe', facture_class);
    url.requestModal(200);
  },
  refreshList: function(print){
    var form = getForm("choice-facture");
    if(!$V(form._pat_name)){
      form.patient_id.value = '';
    }
    var url = new Url("facturation" , "ajax_list_factures");
    url.addFormData(form);
    url.addParam('search_easy[]', $V(form.search_easy), true);
    if (print) {
      url.addParam("print" , 1);
      url.popup();
    }
    else {
      url.requestUpdate("liste_factures");
    }
  },
  gestionFacture: function (sejour_id) {
    var url = new Url('facturation', 'vw_factures_sejour');
    url.addParam('sejour_id', sejour_id);
    url.requestModal();
  },
  refreshAssurance: function(facture_guid) {
    var url = new Url('facturation', 'ajax_list_assurances');
    url.addParam('facture_guid', facture_guid);
    url.requestUpdate('refresh-assurance');
  },
  saveAssurance: function(form) {
    return onSubmitFormAjax(form, {
      onComplete: function () {
        Facture.refreshAssurance($V(form.facture_guid))
      }
    });
  },
  reloadFactureModal: function(facture_id, facture_class, id_reload){
    var url = new Url('facturation', 'ajax_view_facture');
    url.addParam('object_class'  , facture_class);
    url.addParam('facture_id'    , facture_id);
    var facture = $('reload-'+facture_class+'-'+facture_id);
    if (facture) {
      url.requestUpdate('reload-'+facture_class+'-'+facture_id);
    }
    else if (id_reload) {
      url.requestUpdate(id_reload);
    }
  },
  editRepartition: function(facture_id, facture_class){
    var url = new Url("facturation", "ajax_edit_repartition");
    url.addParam("facture_id"   , facture_id);
    url.addParam("facture_class", facture_class);
    url.requestModal();
  },
  editDateFacture: function(form){
    $V(form.cloture, $V(form.ouverture));
    return onSubmitFormAjax(form);
  },
  filterFullName: function(input) {
    table = input.up("table");
    table.select("tr").invoke("show");

    var term = $V(input);
    if (!term) {
      return;
    }

    var view = "._assurance_patient_view";

    table.select(view).each(function (e) {
      if (!e.innerHTML.like(term)) {
        var line = e.up('tr');
        line.hide();
        line.next().hide();
      }
    });
  },
  printFactureFR: function(facture_id, facture_class){
    var url = new Url("facturation", "print_facture");
    url.addParam("facture_id"   , facture_id);
    url.addParam("facture_class", facture_class);
    url.addParam('suppressHeaders', '1');
    url.pop();
  },
  showFiles: function (patient_id, facture_guid) {
    var url = new Url('patients', 'vw_all_docs');
    url.addParam("patient_id", patient_id);
    url.addParam('context_guid', facture_guid);
    url.requestUpdate('files_facture-'+facture_guid);
  },

  togglePratSelector: function (form) {
    form.activeChirSel.toggle();
    form.allChirSel.toggle();
    $V(form.chirSel, form.allChirSel.getStyle('display') !== 'none' ? $V(form.allChirSel) : $V(form.activeChirSel));
  },

  printFacturesByState: function (state, factureClass, page) {
    this.printFacturesByStateParam({state: state, factureClass: factureClass, page: page})
  },
  printFacturesByStateCsv: function (state, factureClass) {
    this.printFacturesByStateParam({state: state, factureClass: factureClass, csv: 1})
  },

  facturesByStateBack: function () {
    $('autres_exports_container').hide();
    $('autres_exports_selection').show();
  },

  printFacturesByStateParam: function (options) {
    if (!options) {
      return false;
    }
    if (!options.csv) {
      this.showPrintFactureContainer();
    }
    var form = getForm('printFrm');
    var url = new Url('facturation', 'print_factures_by_state', options.csv ? 'raw' : null)
      .addParam('state'        , options.state)
      .addParam('facture_class', options.factureClass)
      .addParam('chir_id'      , $V(form.chir))
      .addParam('_date_min'    , $V(form._date_min))
      .addParam('_date_max'    , $V(form._date_max))
      .addNotNullParam('page'  , options.page ? options.page : null)
      .addNotNullParam('csv'   , options.csv ? options.csv : null);
    if (options.csv) {
      url.open();
    }
    else {
      url.requestUpdate('autres_exports_container');
    }
  },

  printFacturesTarmedCotation: function(page, csv) {
    if (!csv) {
      this.showPrintFactureContainer();
    }
    var form = getForm('printFrm');
    var url = new Url('facturation', 'print_factures_tarmed_cot', csv ? 'raw' : null)
      .addParam('chir_id'    , $V(form.chir))
      .addParam('_date_min'  , $V(form._date_min))
      .addParam('_date_max'  , $V(form._date_max))
      .addNotNullParam('page', page ? page : null)
      .addNotNullParam('csv' , csv ? csv : null);
    if (csv) {
      url.open();
    }
    else {
      url.requestUpdate('autres_exports_container');
    }
  },
  showPrintFactureContainer: function() {
    $('autres_exports_container').update('')
      .show();
    $('autres_exports_selection').hide();
  },
  sendBill: function(facture_id, facture_class, prat_id) {
    var url = new Url('tarmed'  , 'ajax_check_bills');
    url.addParam('facture_id', facture_id);
    url.addParam('facture_class', facture_class);
    url.addParam('prat_id', prat_id);
    url.requestModal(800, null, {onClose: function() {
      Facture.reloadFactureModal(facture_id, facture_class, $('load_facture') ? 'load_facture' : null);
    }});
  },
  downloadBills: function(facture_id, facture_class, prat_id) {
    var check_bill = $('check_bill');
    if (check_bill && check_bill.get('count') && check_bill.get('count') != "0") {
      var popupXML = new Url('tarmed', 'ajax_send_file_http', "raw");
      popupXML.addParam('prat_id'      , prat_id);
      popupXML.addParam('facture_id'   , facture_id);
      popupXML.addParam('facture_class', facture_class);
      if (check_bill.get('source_envoi') == "0") {
        popupXML.popup(300, 100);
      }
      else {
        popupXML.requestModal(null, null, {onComplete : Control.Modal.close});
      }
    }
  },

  toggleTarmedHideFields: function(container) {
    container.select('.factu-tarmed-toggle').invoke('toggle');
  },

  toggleDelaiEnvoiXml: function(input) {
    var container = input.up();
    if (input.value !== '1') {
      container.down('button.clock').hide();
    }
    else {
      container.down('button.clock').show();
    }
  },

  printCotations: function(page, csv, categorie_id) {
    var form = getForm('printFrm');
    if(!$V(form.chir)) {
      alert($T('Compta.choose_prat'));
      return false;
    }
    if (!csv) {
      this.showPrintCotationsContainer();
    }
    var url = new Url('facturation', 'print_consultation_cotations', csv ? 'raw' : null)
      .addParam('chir_id'    , $V(form.chir))
      .addParam('_date_min'  , $V(form._date_min))
      .addParam('_date_max'  , $V(form._date_max))
      .addParam('_date_max'  , $V(form._date_max))
      .addNotNullParam('page', page ? page : null)
      .addNotNullParam('categorie_id', categorie_id ? categorie_id : null)
      .addNotNullParam('csv' , csv ? csv : null);
    if (csv) {
      url.open();
    }
    else {
      url.requestUpdate('autres_exports_container');
    }
  },
  showPrintCotationsContainer: function() {
    $('autres_exports_container').update('')
      .show();
    $('autres_exports_selection').hide();
  },
  addKeyUpListener: function() {
    var form = getForm('choice-facture');
    form.num_facture.on('keyup', function (e) {
      if (e.key === "Enter") {
        $V(form.page, 0);
        Facture.refreshList();
      }
    });
  },
  importV11: function(facture_class) {
    var url = new Url('facturation', 'vw_rapprochement_banc');
    url.addParam('facture_class', facture_class);
    url.popup(1200, 600, $T('vw_rapprochement_banc'));
  },
  importCamt054: function(facture_class) {
    var url = new Url('facturation', 'vw_rapprochement_camt054');
    url.addParam('facture_class', facture_class);
    url.popup(1200, 600, $T('vw_rapprochement_banc'));
  },
  viewTotaux: function() {
    var oForm = getForm("printFrm");
    var url = new Url("facturation", "ajax_total_cotation");
    url.addParam("chir_id", $V(oForm.chir));
    url.addParam('date_min', $V(oForm._date_min));
    url.addParam('date_max', $V(oForm._date_max));
    url.popup(1000, 600);
  },
  TdbCotation: {
    selectedLines: [],
    currentPage: 0,
    refreshList: function(form, page) {
      this.currentPage = typeof(page) !== 'undefined' ? page : this.currentPage;
      this.selectedLines = [];
      var url = new Url('facturation', 'vw_tdb_cotation')
        .addNotNullParam('page', typeof(page) !== 'undefined' ? page : this.currentPage)
        .addNotNullParam('get_consults', 1);
      if (form) {
        url.addFormData(form)
          .addParam('use_disabled_praticien', $V(form.use_disabled_praticien) ? 1 : 0)
          .addParam('praticien_id', $V(form.chirSel));
      }
      url.requestUpdate('consultations_list');
    },
    allCheck: function(input) {
      $('consultations_list').select('.tdb-cotation-check').each(
        function(e) {
          e.checked = input.checked;
          this.checkLine(e, false);
        }.bind(this)
      );
      this.controlCount();
    },
    checkLine: function(input, controlAll) {
      if (input.checked) {
        this.selectedLines.push(input.get('consultation-id'));
      }
      else {
        var oldLines = JSON.parse(JSON.stringify(this.selectedLines));
        this.selectedLines = [];
        oldLines.each(
          function(e) {
            if (e === input.get('consultation-id')) {
              return;
            }
            this.selectedLines.push(e);
          }.bind(this)
        );
      }
      if (typeof(controlAll) === 'undefined' || controlAll) {
        this.controlAllCheck();
        this.controlCount();
      }
    },
    controlAllCheck: function() {
      var toCheck = true;
      $('consultations_list').select('.tdb-cotation-check').each(
        function(e) {
          if (!toCheck || e.checked) {
            return;
          }
          toCheck = false;
        }
      );
      $('tdb_cotation_all_check').checked = toCheck;
    },
    controlCount: function() {
      $('tdb_cotation_multiple_cloture_button')
        .update($T('CConsultation-action-close-cotation') + ' (' + this.selectedLines.length + ')')
        .disabled = this.selectedLines.length === 0;
    },
    clotureCotation: function() {
      if (!confirm($T('CConsultation-action-close-cotation') + ' (' + this.selectedLines.length + ') ?')) {
        return;
      }
      new Url('facturation', 'ajax_tdb_cotation_multiple_cloture')
        .addParam('consultation_ids[]', this.selectedLines, true)
        .requestUpdate(
          'tdb_cotation_multiple_cloture',
          function() {
            this.refreshList();
          }.bind(this)
        );
    }
  }
};
