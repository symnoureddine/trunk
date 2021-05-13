/**
 * @package Mediboard\CCAM
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

ActesNGAP = {
  changePage: function(target, page) {
    ActesNGAP.refreshList(target, null, null, page);
  },

  refreshList: function(target, order_col, order_way, page) {
    if (!target) {
      target = $('listActesNGAP');
    }
    else if (typeof target == 'string') {
      target = $(target);
    }

    var url = new Url("dPcabinet", "httpreq_vw_actes_ngap");
    url.addParam("object_id", target.get('object_id'));
    url.addParam("object_class", target.get('object_class'));
    if (target.get('executant_id')) {
      url.addParam('executant_id', target.get('executant_id'));
    }
    if (target.get('execution')) {
      url.addParam('execution', target.get('execution'));
    }
    if (target.get('display')) {
      url.addParam('display', target.get('display'));
    }
    if (target.get('code')) {
      url.addParam('code', target.get('code'));
    }
    if (target.get('coefficient')) {
      url.addParam('coefficient', target.get('coefficient'));
    }
    if (target.get('show_tarifs')) {
      url.addParam('show_tarifs', target.get('show_tarifs'));
    }
    url.addParam('target', target.id);
    console.log('page: %o', page);
    if (!Object.isUndefined(page)) {
      url.addParam('page', page);
    }
    if (order_col) {
      url.addParam('order_col', order_col);
      target.writeAttribute('data-order_col', order_col);
    }
    else if (target.get('order_col')) {
      url.addParam('order_col', target.get('order_col'));
    }
    if (order_way) {
      url.addParam('order_way', order_way);
      target.writeAttribute('data-order_way', order_way);
    }
    else if (target.get('order_way')) {
      url.addParam('order_way', target.get('order_way'));
    }

    var object_guid = target.get('object_class') + '-' + target.get('object_id');
    if (getForm('filterActs-' + object_guid)) {
      var filterForm = getForm('filterActs-' + object_guid);
      url.addParam('filter_executant_id', $V(filterForm.elements['executant_id']));
      url.addParam('filter_function_id', $V(filterForm.elements['function_id']));
      url.addParam('filter_facturable', $V(filterForm.elements['facturable']));
      url.addParam('filter_date_min', $V(filterForm.elements['date_min']));
      url.addParam('filter_date_max', $V(filterForm.elements['date_max']));
    }

    url.requestUpdate(target, {onComplete: function() {
      if ($('count_ngap_' + object_guid)) {
        var url = new Url('ccam', 'ajax_update_acts_counter');
        url.addParam('subject_guid', object_guid);
        url.addParam('type', 'ngap');
        url.requestUpdate('count_ngap_' + object_guid, {
          insertion: function (element, content) {
            element.innerHTML = content;
          }
        });
      }
    }});
  },

  remove: function(form) {
    $V(form.del, 1);
    form.onsubmit();
  },

  edit: function(acte_id, target) {
    new Url('cabinet', 'ajax_edit_acte_ngap')
      .addParam('acte_id', acte_id)
      .requestModal('800px', '550px', {onClose: function() {ActesNGAP.refreshList(target);}});
  },

  checkExecutant: function() {
    if (!$V(getForm('editActeNGAP')._executant_spec_cpam)) {
      alert($T("CActeNGAP-specialty-undefined_user"));
    }
  },

  checkNumTooth: function(input, view) {
    var num_tooth = $V(input);

    if (num_tooth < 11 || (num_tooth > 18 && num_tooth < 21) || (num_tooth > 28 && num_tooth < 31) || (num_tooth > 38 && num_tooth < 41) || (num_tooth > 48 && num_tooth < 51) || (num_tooth > 55 && num_tooth < 61) || (num_tooth > 65 && num_tooth < 71) || (num_tooth > 75 && num_tooth < 81) ||  num_tooth > 85) {
      alert("Le numéro de dent saisi ne correspond pas à la numérotation internationale!");
    }
    else {
      ActesNGAP.syncCodageField(this, view);
    }
  },

  editDEP: function(view) {
    Modal.open('modal_dep' + view, {showClose: true});
  },

  toggleDateDEP: function(element, view) {
    if (element.value == 1) {
      $('accord_infos' + view).show();
    }
    else {
      $('accord_infos' + view).hide();
    }
  },

  syncDEPFields: function(form, view) {
    ActesNGAP.syncCodageField(form.down('[name="accord_prealable"]:checked'), view);
    ActesNGAP.syncCodageField(form.date_demande_accord, view);
    ActesNGAP.syncCodageField(form.reponse_accord, view);
    Control.Modal.close();
  },

  checkDEP: function(view) {
    var element = $('info_dep' + view);
    var form = getForm('editActeNGAP-accord_prealable' + view);

    if (element != null) {
      if ($V(form.accord_prealable) == '1' && $V(form.date_demande_accord) && $V(form.reponse_accord)) {
        element.setStyle({color: '#197837'});
      }
      else {
        element.setStyle({color: '#ffa30c'});
      }
    }
  },

  setCoefficient: function(element, view) {
    var value = $V(element)
    if (value != '') {
      ActesNGAP.syncCodageField(element, view);
    }
  },

  refreshTarif: function(view) {
    if ($('inc_codage_ngap_button_create')) {
      $('inc_codage_ngap_button_create').disabled = true;
    }
    var form = getForm('editActeNGAP' + view);
    var url = new Url("cabinet", "httpreq_vw_tarif_code_ngap");
    url.addElement(form.acte_ngap_id);
    url.addElement(form.quantite);
    url.addElement(form.code);
    url.addElement(form.coefficient);
    url.addElement(form.demi);
    url.addElement(form.complement);
    url.addElement(form.executant_id);
    url.addElement(form.gratuit);
    url.addElement(form.execution);
    url.addElement(form.taux_abattement);
    url.addParam('view', view);
    if ($V(form.acte_ngap_id)) {
      url.addParam('disabled', 1);
    }
    url.requestUpdate('tarifActe' + view, function() {
      if ($('inc_codage_ngap_button_create')) {
        $('inc_codage_ngap_button_create').disabled = false;
      }
    });
  },

  syncCodageField: function(element, view, fire) {
    fire = Object.isUndefined(fire) ? true : fire;

    if (element.name == 'quantite' || element.name == 'coefficient') {
      if (parseFloat($V(element)) <= 0) {
        $V(element, 1);
      }
    }

    var form = getForm('editActeNGAP' + view);
    var fieldName = element.name;
    var fieldValue = $V(element);
    $V(form[fieldName], fieldValue, fire);
  },

  changeTauxAbattement: function(element, view) {
    if ($V(element) == 0) {
      $V(getForm('editActeNGAP-gratuit' + view).elements['gratuit'], '1', false);
    }
    else {
      $V(getForm('editActeNGAP-gratuit' + view).elements['gratuit'], '0', false);
    }
    this.syncCodageField(getForm('editActeNGAP-gratuit' + view).elements['gratuit'], view, false);

    this.syncCodageField(element, view);
  },

  submit: function(form, target) {
    if (!$V(form.acte_ngap_id)) {
      ActesNGAP.checkExecutant();
    }
    return onSubmitFormAjax(form, function() {
      ActesNGAP.refreshList(target);
      if (typeof DevisCodage !== 'undefined') {
        target = $(target);
        DevisCodage.refresh(target.get('object_id'));
      }
    });
  },

  duplicate: function(acte_guid, target) {
    target = $(target);
    if (target && target.get('object_class') == 'CSejour') {
      var url = new Url('ccam', 'ajax_duplicate_ngap');
      url.addParam('codable_guid', target.get('object_class') + '-' + target.get('object_id'));
      url.addParam('acte_guid', acte_guid);
      url.requestModal(null, null, {onClose: function() {ActesNGAP.refreshList(target)}});
    }
  }
};
