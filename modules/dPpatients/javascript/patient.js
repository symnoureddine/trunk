/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// $Id: $

/** TODO: Factoriser ceci pour ne pas avoir a etendre l'objet (sinon Patient.create est ecrasé) */
Patient = Object.extend({
  tabs:                      null,
  modulePatient:             'patients',
  form_search:               'find',
  adult_age:                 null,
  anonymous_sexe:            null,
  anonymous_naissance:       null,
  copying_prenom:            null,

  assure_values: [
    'nom', 'prenom', 'prenoms', 'nom_jeune_fille',
    'sexe', 'naissance',
    'cp_naissance', 'pays_naissance_insee', 'lieu_naissance', 'profession',
    'adresse', 'rang_naissance'
  ],

  view:                function (patient_id) {
    new Url(this.modulePatient, 'vw_full_patients', 'tab').addParam('patient_id', patient_id).redirectOpener();
  },
  viewModal:           function (patient_id, onclose) {
    new Url(this.modulePatient, 'vw_full_patients').addParam('patient_id', patient_id).modal({
      width:   '90%',
      height:  '90%',
      onClose: onclose
    });
  },
  history:             function (patient_id) {
    new Url('patients', 'vw_history').addParam("patient_id", patient_id).popup(600, 500, 'patient history');
  },
  print:               function (patient_id) {
    new Url('patients', 'print_patient').addParam('patient_id', patient_id).popup(700, 550, 'Patient');
  },
  showDossierMedical:  function (patient_id) {
    new Url('cabinet', 'httpreq_vw_antecedents')
      .addParam('patient_id', patient_id)
      .addParam('sejour_id', '') // A passer car le script récupère sinon le sejour_id en session
      .addParam('show_header', 1)
      .modal({width: '80%', height: '80%'});
  },
  showSummary:         function (patient_id) {
    new Url('cabinet', 'vw_resume')
      .addParam("patient_id", patient_id)
      .popup(800, 500, 'Summary' + (Preferences.multi_popups_resume == '1' ? patient_id : null));
  },
  create:              function (form) {
    new Url('patients', 'vw_edit_patients', 'tab').addParam('patient_id', 0).addParam('useVitale', $V(form.useVitale)).addParam('covercard', $V(form.covercard)).addParam('name', $V(form.nom)).addParam('firstName', $V(form.prenom)).addParam('naissance_day', $V(form.Date_Day)).addParam('naissance_month', $V(form.Date_Month)).addParam('naissance_year', $V(form.Date_Year)).redirect();
  },
  createModal:         function (form, callback, onclose) {
    new Url('patients', 'vw_edit_patients').addParam('patient_id', 0).addParam('useVitale', $V(form.useVitale)).addParam('covercard', $V(form.covercard)).addParam('name', $V(form.nom)).addParam('firstName', $V(form.prenom)).addParam('naissance_day', $V(form.Date_Day)).addParam('naissance_month', $V(form.Date_Month)).addParam('naissance_year', $V(form.Date_Year)).addParam('callback', callback).addParam('modal', 1).modal({
      width:   '90%',
      height:  '90%',
      onClose: onclose
    });
  },
  edit:                function (patient_id, use_vitale) {
    new Url('patients', 'vw_edit_patients', 'tab').addParam('patient_id', patient_id).addParam('use_vitale', use_vitale).redirectOpener();
  },
  editModal:           function (patient_id, use_vitale, callback, onclose, fragment, validate_identity) {
    new Url('patients', 'vw_edit_patients')
      .addParam('patient_id', patient_id)
      .addParam('use_vitale', use_vitale)
      .addParam('callback', callback)
      .addParam('validate_identity', validate_identity)
      .addParam('modal', 1)
      .setFragment(fragment).modal(
        {
          width:   '90%',
          height:  '90%',
          onClose: onclose
        }
      );
  },

  /**
   * Vérification du lieu de naissance :
   *  - pays obligatoire
   *  - commune obligatoire si le pays sélectionné est la france
   *
   * @param form
   * @returns {boolean}
   */
  checkLieuNaissance: function(form) {
    var pays_naissance = $V(form._source__pays_naissance_insee).toLowerCase();
    var commune_naissance = $V(form._source_lieu_naissance);

    if (!commune_naissance && !pays_naissance) {
      alert($T('CPatient-Pays naissance mandatory'));
      form._source__pays_naissance_insee.tryFocus();
      return false;
    }

    if (!commune_naissance && (pays_naissance === 'france')) {
      alert($T('CPatient-Commune naissance mandatory'));
      form._source_lieu_naissance.tryFocus();
      return false;
    }

    return true;
  },

  copyPrenom: function(element, from_justif) {
    if (Patient.copying_prenom) {
      return;
    }

    Patient.copying_prenom = true;

    var field_prenom = 'prenom';
    var field_prenoms = 'prenoms';

    if (from_justif) {
      field_prenom = '_source_' + field_prenom;
      field_prenoms = '_source_' + field_prenoms;
    }

    // Trim sur les espaces
    $V(element, element.value.trim());

    // Remplacement de 2 espaces et plus par un seul espace
    $V(element, element.value.replace(/ {2,}/g, ' '));

    var split_prenoms = $V(element.form.elements[field_prenoms]).split(' ');

    switch (element.name) {
      default:
      case field_prenom:
        // On remplaçe les espaces par des tirets
        $V(element, element.value.replace(/ /g, '-'));

        $V(element.form.elements[field_prenoms], $V(element));

        // Retrait de la première entrée de la liste des prénoms
        split_prenoms.shift();

        // Ajout des autres prénoms à la liste
        if (split_prenoms.length) {
          $V(element.form.elements[field_prenoms], $V(element.form.elements[field_prenoms]) + ' ' + split_prenoms.join(' '));
        }

        break;

      case field_prenoms:
        if (split_prenoms.length) {
          $V(element.form.elements[field_prenom], split_prenoms[0]);
        }
    }

    Patient.copying_prenom = false;
  },

  confirmCreation:     function (form) {
    if (!checkForm(form)) {
      return false;
    }

    if (!Patient.checkBirthdate(form) && $V(form.modal)) {
      return false;
    }

    SiblingsChecker.submit = 1;
    SiblingsChecker.request(form);
    return false;
  },
  confirmPurge:        function (form, pat_view) {
    if (confirm($T('CPatient-Alert confirm purge'))) {
      $V(form._purge, "1");
      confirmDeletion(form, {
        typeName: 'le patient',
        objName:  pat_view
      });
    }
  },
  exportVcard:         function (patient_id) {
    new Url('patients', 'ajax_export_vcard').addParam('patient_id', patient_id).addParam('suppressHeaders', 1).pop(700, 550, 'Patient');
  },
  openINS:             function ($id) {
    new Url('patients', 'ajax_history_ins')
      .addParam('patient_id', $id)
      .requestModal();
  },
  doMerge:             function (oForm) {
    new Url('system', 'object_merger')
      .addParam('objects_class', 'CPatient')
      .addParam('objects_id', $V(oForm['objects_id[]']).join('-'))
      .popup(800, 600, 'merge_patients');
  },
  doLink:              function (oForm) {
    new Url('patients', 'do_link', 'dosql')
      .addParam('objects_id', $V(oForm['objects_id[]']).join('-'))
      .requestUpdate('systemMsg', {
        method: 'post'
      });
  },
  doPurge:             function (patient_id) {
    new Url(this.modulePatient, 'vw_idx_patients')
      .addParam('dosql', 'do_patients_aed')
      .addParam('del', 1)
      .addParam('_purge', 1)
      .addParam('patient_id', patient_id)
      .requestUpdate('systemMsg', {
        method: 'post'
      });
  },
  isMobilePhone:       function (phoneNumber) {
    var firstDigits = phoneNumber.substring(0, 2);
    return (firstDigits == '06' || firstDigits == '07');
  },
  checkMobilePhone:    function (element) {
    var div = $('mobilePhoneFormat');
    var phoneNumber = element.value.replace(/[_ ]/g, '');
    if (phoneNumber.length < 2 || Calendar.ref_pays != 1) {
      div.hide();
    } else {
      Patient.isMobilePhone(phoneNumber) ? div.hide() : div.show();
    }
  },
  checkNotMobilePhone: function (element) {
    var div = $('phoneFormat');
    var phoneNumber = element.value.replace(/[_ ]/g, '');
    if (phoneNumber.length < 2 || Calendar.ref_pays != 1) {
      div.hide();
    } else {
      Patient.isMobilePhone(phoneNumber) ? div.show() : div.hide();
    }
  },

  toggleSearch: function () {
    $$('.field_advanced').invoke('toggle');
    $$('.field_basic').invoke('toggle');
  },

  togglePraticien: function () {
    var praticien = getForm(Patient.form_search).prat_id;
    var praticien_message = $('prat_id_message');
    var enough = Patient.checkEnoughTraits();

    praticien.setVisible(enough);
    praticien_message.setVisible(!enough);

    if (!enough) {
      $V(praticien, '');
    }
  },

  checkEnoughTraits: function () {
    var form = getForm(Patient.form_search);

    return $V(form.nom).length >= 2 ||
      $V(form.prenom).length >= 2 ||
      $V(form.cp).length >= 2 ||
      $V(form.ville).length >= 2 ||
      $V(form.Date_Year) ||
      ($V(form.Date_Day) && $V(form.Date_Month) && $V(form.Date_Year));
  },

  fillBMRBHeId: function (bmr_bhe_id) {
    $V(getForm('editBMRBHRe').bmr_bhre_id, bmr_bhe_id);
  },

  showFamilyLinkWithPatient: function (parent_id_1, parent_id_2, patient_id) {
    new Url('patients', 'vw_family_link')
      .addParam('patient_id', patient_id)
      .addParam('parent_id_1', parent_id_1)
      .addParam('parent_id_2', parent_id_2)
      .requestJSON(function (families) {
        var show_family = $('show_family');
        if (!show_family) {
          return;
        }

        var array_size = Object.keys(families).length;
        var comma = ", ";

        if (array_size) {
          var counter = 1;
          Object.keys(families).each(function (id) {
            var family = families[id];

            if (counter == array_size) {
              comma = "";
            }

            var elementDOM = DOM.span({
              className:   '',
              onmouseover: "ObjectTooltip.createEx(this, 'CPatient-" + family["id"] + "')"
            }, family["view"] + comma);

            counter++;

            show_family.insert(elementDOM);
          });
        }
      });
  },

  callbackFamilyLink: function (patient_family_link_id) {
    $V(getForm('FrmPatientFamily').patient_family_link_id, patient_family_link_id);
  },

  getCoordinatesParent: function (parent_id) {
    var form = getForm('editFrm');
    new Url('patients', 'ajax_coordonnees_parent')
      .addParam('parent_id', parent_id)
      .requestJSON(function (coordonnees) {
        $V(form.adresse, coordonnees['adresse']);
        $V(form.cp, coordonnees['cp']);
        $V(form.ville, coordonnees['ville']);
        $V(form.pays, coordonnees['pays']);

        if (coordonnees['adresse']) {
          SystemMessage.notify('<div class="info">' + $T('CPatient-msg-Patient coordinates copied') + '</div><div class="warning">' + $T('CPatient-msg-Do not forget to save') + '</div>', false);
        }
      });
  },

  getAntecedentParents: function (patient_id, context_class, context_id) {
    new Url('patients', 'ajax_antecedents_parents')
      .addParam('patient_id', patient_id)
      .addParam('context_class', context_class)
      .addParam('context_id', context_id)
      .requestModal('90%', '90%');
  },

  sendAntecedentsParent: function (object_class, object_id) {
    var atcds_selected = [];

    $('antecedents_parent1').select('input[name=antecedent_parent1]:checked').each(function (elt) {
      var tbody = elt.up('tbody');
      atcds_selected.push(tbody.getAttribute('id'));
    });

    $('antecedents_parent2').select('input[name=antecedent_parent2]:checked').each(function (elt) {
      var tbody = elt.up('tbody');
      atcds_selected.push(tbody.getAttribute('id'));
    });

    new Url('patients', 'controllers/do_send_antecedents')
      .addParam('atcds_selected[]', atcds_selected, true)
      .addParam('object_class', object_class)
      .addParam('object_id', object_id)
      .requestUpdate("systemMsg", {
        onComplete: function () {
          Control.Modal.close();
          if (window.DossierMedical) {
            DossierMedical.reloadDossierPatient();
          }
        }
      });

    return false;
  },

  copyAssureValues: function (element) {
    // Hack pour gérer les form fields
    var sPrefix = element.name[0] == "_" ? "_assure" : "assure_";
    var eOther = element.form[sPrefix + element.name];

    if (element.name === 'naissance') {
      $V(element.form['assure_naissance_amo'], $V(element));
    }

    // Copy value
    $V(eOther, $V(element));

    // Radio buttons seem to be null, et valuable with $V
    if (element.type != 'radio') {
      eOther.fire("mask:check");
    }
  },

  copyIdentiteAssureValues: function (element) {
    if (element.form.qual_beneficiaire.value === '00') {
      this.copyAssureValues(element);
    }
  },

  delAssureValues: function () {
    var form = getForm('editFrm');
    this.assure_values.each(function (_input_name) {
      $V(form.elements['assure_' + _input_name], '');
    });
  },

  copieAssureValues: function () {
    var form = getForm('editFrm');

    this.assure_values.each(function (_input_name) {
      $V(form.elements['assure_' + _input_name], $V(form.elements[_input_name]));

      if (_input_name === 'naissance') {
        $V(form.elements['assure_' + _input_name + '_amo'], $V(form.elements[_input_name]));
      }
    });
  },

  loadDocItems: function (patient_id) {
    new Url('files', 'httpreq_vw_listfiles')
      .addParam('selClass', 'CPatient')
      .addParam('selKey', patient_id)
      .requestUpdate('listView');
  },

  reloadListFileEditPatient: function (action, category_id) {
    if (!window.reloadListFile) {
      return;
    }
    reloadListFile(action, category_id);
  },

  calculFinAmo: function () {
    var form = getForm("editFrm");
    var sDate = $V(form.fin_amo);

    if ($V(form.cmu) === 1 && sDate === "") {
      date = new Date;
      date.addDays(365);
      $V(form.fin_amo, date.toDATE());
      $V(form.fin_amo_da, date.toLocaleDate());
    }
  },

  checkFinAmo: function () {
    var form = getForm("editFrm");
    var fin_amo = $V(form.fin_amo);
    var warning = $("fin_amo_warning");
    var tab = $$("#tab-patient a[href='#beneficiaire']")[0];

    if (fin_amo && fin_amo < (new Date()).toDATE()) {
      warning.show();
      tab.addClassName("wrong");
    } else {
      warning.hide();
      tab.removeClassName("wrong");
    }
  },

  togglePrenomsList: function (element) {
    $('patient_identite').select('.prenoms_list').invoke('toggle');
    Element.classNames(element).flip('up', 'down');
  },

  toggleActivitePro: function (value) {
    $$('.activite_pro').invoke(value != '' ? 'show' : 'hide');
  },

  selectFirstEnabled: function (select) {
    var found = false;
    $A(select.options).each(function (o, i) {
      if (!found && !o.disabled && o.value != '') {
        $V(select, o.value);
        found = true;
      }
    });
  },

  disableOptions: function (select, list) {
    $A(select.options).each(function (o) {
      o.disabled = list.include(o.value);
    });

    if (select.value == '' || select.options[select.selectedIndex].disabled) {
      this.selectFirstEnabled(select);
    }
  },

  changeCivilite: function (assure) {
    var form = getForm('editFrm');
    var civilite = null;
    var valueSexe = null;
    var valueNaissance = null;

    if (assure) {
      civilite = 'assure_civilite';
      valueSexe = $V(form.assure_sexe);
      valueNaissance = $V(form.assure_naissance);
    } else {
      civilite = 'civilite';
      valueSexe = $V(form.sexe);
      valueNaissance = $V(form.naissance);
    }

    switch (valueSexe) {
      case 'm':
        this.disableOptions(form[civilite], $w('mme mlle vve'));
        break;

      case 'f':
        this.disableOptions(form[civilite], $w('m'));
        break;

      default:
        this.disableOptions(form[civilite], $w('m mme mlle enf dr pr me vve'));
        break;
    }

    if (valueNaissance) {
      var date = new Date();
      var naissance = valueNaissance.split('/')[2];
      if (((date.getFullYear() - this.adult_age) <= naissance) && (naissance <= (date.getFullYear()))) {
        $V(form[civilite], "enf");
      }
    }
  },

  resetFieldsForAnonymous: function () {
    var form = getForm('editFrm');

    $V(form.sexe, 'm');
    if (this.anonymous_sexe) {
      $V(form.sexe, this.anonymous_sexe);
    }

    $V(form.naissance, '1970-01-01');
    if (this.anonymous_naissance) {
      $V(form.naissance, this.anonymous_naissance);
    }

    $V(form.civilite, '');
    $V(form.situation_famille, 'S');
    $V(form.mdv_familiale, '');
    $V(form.condition_hebergement, '');
    $V(form.rang_naissance, 1);
    $V(form.cp_naissance, '');
    $V(form.lieu_naissance, '');
    $V(form._pays_naissance_insee, '');
    $V(form.niveau_etudes, '');
    $V(form.activite_pro, '');
    $V(form.profession, '');
    $V(form._csp_view, '');
    $V(form.fatigue_travail, '');
    $V(form.travail_hebdo, '');
    $V(form.transport_jour, '');
    $V(form.matricule, '');
    $V(form.qual_beneficiaire, '00');
    form.tutelle[0].checked = true;
    form.don_organes[0].checked = true;
    form.directives_anticipees[2].checked = true;
    $V(form.__vip, '');
    $V(form.deces, '');
    $V(form.adresse, '');
    $V(form.cp, '');
    $V(form.ville, '');
    $V(form.pays, '');
    $V(form.phone_area_code, '');
    $V(form.tel, '');
    $V(form.tel2, '');
    $V(form.__allow_sms_notification, '');
    $V(form.tel_pro, '');
    $V(form.tel_autre, '');
    $V(form.tel_autre_mobile, '');
    $V(form.email, '');
    $V(form.__allow_email, '');
    $V(form.rques, '');
  },

  anonymous: function () {
    $V("editFrm_nom", "anonyme");
    $V("editFrm_prenom", "anonyme");
    $V("editFrm_nom_jeune_fille", "anonyme");

    this.resetFieldsForAnonymous();
  },

  checkDoublon: function () {
    var form = getForm("editFrm");
    if ($V(form.nom) && $V(form.prenom) && $V(form.naissance)) {
      SiblingsChecker.request(form);
    }
  },

  refreshInfoTutelle: function (tutelle) {
    new Url('patients', 'ajax_check_correspondant_tutelle')
      .addParam('patient_id', $V(getForm('editFrm').patient_id))
      .addParam('tutelle', tutelle)
      .requestUpdate('alert_tutelle');
  },

  accessibilityData: function () {
    new Url('patients', 'ajax_acces_patient')
      .addParam('patient_id', $V(getForm('editFrm').patient_id))
      .requestModal('70%', '70%');
  },

  showAdvanceDirectives: function () {
    new Url('patients', 'vw_list_directives_anticipees')
      .addParam('patient_id', $V(getForm('editFrm').patient_id))
      .requestModal(
        '70%',
        '70%',
        {
          onClose: function () {
            var warningExists = ($$('.no-directives').length > 0);
            AnticipatedDirectives.number_directives = $$('.a-directive').length;

            if (AnticipatedDirectives.number_directives === 0 && !warningExists) {
              AnticipatedDirectives.addWarningNoDirectives(true);
            }
            else if (AnticipatedDirectives.number_directives > 0 && warningExists) {
              AnticipatedDirectives.removeWarningNoDirectives();
            }
          }
        }
      );
  },

  checkAdvanceDirectives: function (elt, forceDisplay) {
    if (elt.value == 1) {
      this.showAdvanceDirectives();
    }
    else {
      AnticipatedDirectives.removeWarningNoDirectives();
    }
  },

  /**
   * Check if the birthdate is correct
   *
   * @param form
   * @returns {boolean}
   */
  checkBirthdate: function (form) {
    var current_year = new Date().getFullYear();
    var birthdate = $V(form.naissance);
    var birthdate_year = new Date(birthdate).getFullYear();

    if (birthdate_year > current_year) {
      alert($T('CPatient-msg-You cannot enter a date of birth greater than the current year'));
      return false;
    }

    return true;
  },

  addJustificatif: function(patient_id) {
    new Url('patients', 'vw_add_justificatif')
      .addParam('patient_id', patient_id)
      .requestModal(600, 680);
  },

  submitJustificatif: function() {
    var form_from = getForm('addJustificatif');
    var form_to   = getForm('editFrm');

    if (!form_from || !form_to) {
      return false;
    }

    if (($V(form_to._douteux) === '1') || ($V(form_to._fictif) === '1')) {
      if (!confirm($T('CPatient-Confirm provisoire status will be kept despite adding proof'))) {
        Control.Modal.close();
        return false;
      }
    }

    $V(form_to.elements['_type_justificatif'], $V(form_from.elements['_type_justificatif']));
    $V(form_to.elements['_source__date_fin_validite'], $V(form_from.elements['_source__date_fin_validite']));

    var submit_form = $V(form_to.patient_id) !== '';

    if (!checkForm(form_from)) {
      return false;
    }

    if (!this.checkLieuNaissance(form_from)) {
      return false;
    }

    [
      'nom_jeune_fille', 'nom', 'prenom', 'prenoms',
      'prenom_usuel', 'naissance', 'sexe', 'civilite',
      '_pays_naissance_insee', 'cp_naissance', 'lieu_naissance', 'commune_naissance_insee'
    ].each(function(_input_name) {
        var input_name_source = '_source_' + _input_name;

        if (!form_from.elements[input_name_source]
            || !form_to.elements[input_name_source] || !form_to.elements[_input_name]) {
          return;
        }

        // Copie des champs à la fois dans les inputs visibles (champs patients) et hidden (champs de la source en modification du dossier patient)
        if ($V(form_from.elements[input_name_source])) {
          $V(form_to.elements[input_name_source], $V(form_from.elements[input_name_source]));
        }

        if ($V(form_from.elements[input_name_source])) {
          $V(form_to.elements[_input_name], $V(form_from.elements[input_name_source]));
        }
    });

    if (submit_form && !checkForm(form_to)) {
      return false;
    }

    if (!form_to.elements['formfile[]']) {
      if ($V(form_to.modal) === '1') {
        var img = form_from.down('img');

        if (!img) {
          alert($T('CSourceIdentite-Justificatif mandatory'));
          return false;
        }

        form_to.insert(DOM.input({
          type:        'hidden',
          name:        'formfile[]',
          value:       'Paper.jpg',
          'data-blob': 'blob'
        })
          .store("blob", this.dataURItoBlob(img.src)));
      } else {
        var input_file = form_from.down("input[type=file][name='formfile[]']");

        if (!$V(input_file)) {
          alert($T('CSourceIdentite-Justificatif mandatory'));
          return false;
        }

        input_file = input_file.remove();
        input_file.hide();

        form_to.insert(input_file);
      }
    }

    if (submit_form) {
      return form_to.onsubmit();
    }

    Control.Modal.close();

    return false;
  }
}, window.Patient);
