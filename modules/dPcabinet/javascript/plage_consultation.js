/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

PlageConsultation  = window.PlageConsultation || {
  status_images : ["images/icons/status_red.png", "images/icons/status_orange.png", "images/icons/status_green.png"],
  modal: null,
  url: null,

  edit: function(plageconsult_id, debut, callback) {
    var url = new Url('cabinet', 'edit_plage_consultation');
    url.addParam('plageconsult_id', plageconsult_id);
    url.addParam('debut', debut);
    url.requestModal(800, "100%");
    this.modal = url.modalObject;
    this.url = url;
    if (callback) {
      url.modalObject.observe("afterClose", callback);
    }
  },

  print: function(plageconsult_id) {
    var url = new Url;
    url.setModuleAction("cabinet", "print_plages");
    url.addParam("plage_id", plageconsult_id);
    url.popup(700, 550, "Planning");
  },

  /**
   * Affiche les plages à imprimer
   *
   * @param {string} plagesconsult_ids Identifiants des plages à imprimer, séparés par un pipe
   */
  printPlages: function(plagesconsult_ids) {
    new Url('cabinet', 'print_plages')
      .addParam("plagesconsult_ids", plagesconsult_ids)
      .popup(700, 550, "Planning");
  },

  /**
   * Affiche la liste des consultations à imprimer
   *
   * @param {date} date - date du jour souhaité
   * @param {string} content_class - Objet concerné par le filtre
   * @param {string} content_id - ID de l'objet
   */
  printConsult: function(date, content_class, content_id) {
    var url = new Url;
    url.setModuleAction("cabinet", "print_plages");
    url.addParam('_date_min', date);
    url.addParam('_date_max', date);
    if(content_class === 'CMediusers') {
      url.addParam('chir', content_id);
    }
    if(content_class === 'CFunctions') {
      url.addParam('function_id', content_id);
    }
    url.popup(700, 550, "Planning");
  },
  
  onSubmit: function(form) {
    return onSubmitFormAjax(form, function() {
      PlageConsultation.refreshList();
      PlageConsultation.modal.close();
    });
  },
  
  checkForm: function(form, modal) {
    if (!checkForm(form)) {
      return false;
    }

    if (form.nbaffected.value!= 0 && form.nbaffected.value != "") {
      if (!(confirm("Attention, cette plage contient déjà " + form.nbaffected.value + " consultation(s).\n\nVoulez-vous appliquer les modifications?"))){
        return false;
      }
    }

    //pour le compte de = chir sel
    if ($V(form.chir_id) == $V(form.pour_compte_id)) {
      alert("Vous ne pouvez pas créer une plage pour le compte de vous-même");
      return false;
    }

    // remplacement de soit même
    if ($V(form.chir_id) == $V(form.remplacant_id)) {
      alert("Vous ne pouvez pas vous remplacer vous-même");
      return false;
    }

    if (modal) {
      return onSubmitFormAjax(form, {onComplete: Control.Modal.close});
    }
    else {
      return true;
    }
  },
  
  resfreshImageStatus : function(element){
    if (!element.get('id')) {
      return;
    }
  
    element.title = "";
    element.src   = "style/mediboard_ext/images/icons/loading.gif";
    
    url.addParam("source_guid", element.get('guid'));
    url.requestJSON(function(status) {
      element.src = PlageConsultation.status_images[status.reachable];
      });
  },

  promptBackup: function() {
    var freq = Preferences.dPcabinet_offline_mode_frequency;
    if (!freq || freq == 0) {
      return;
    }

    var latestBackup = store.get("cabinet-backup");
    var downloadDate = null;

    if (!latestBackup || !latestBackup[User.id] || latestBackup[User.id].ask + freq*3600000 < Date.now()) {
      downloadDate = (latestBackup && latestBackup[User.id] && latestBackup[User.id].download) || null;

      var date = (new Date(downloadDate)).toLocaleDateTime();
      var msg  = $T("dPcabinet-msg-Do you want to make a backup?") + "\n";

      if (downloadDate) {
        msg += $T("dPcabinet-msg-Latest one was at %s", date);
      }
      else {
        msg += $T("dPcabinet-msg-You never made a backup from this browser");
      }

      if (confirm(msg)) {
        PlageConsultation.downloadBackup();
        return;
      }
    }

    latestBackup = latestBackup || {};
    latestBackup[User.id] = {
      download: downloadDate,
      ask:      Date.now()
    };

    store.set("cabinet-backup", latestBackup);
  },
  downloadBackup: function() {
    var url = new Url("cabinet", "download_backup");
    url.addParam("_aio", 1);
    url.addParam("function_id", User.function.id);
    url.pop(500, 300, "Sauvegarde cabinet");

    var latestBackup = store.get("cabinet-backup") || {};

    latestBackup[User.id] = {
      download: Date.now(),
      ask:      Date.now()
    };

    store.set("cabinet-backup", latestBackup);
  },
  showPratsByFunction: function(function_id, all_prat) {
    var url = new Url("cabinet", "ajax_show_prats_by_function");
    url.addParam("function_id"   , function_id);
    url.addParam("prats_selected", all_prat);
    url.requestUpdate("filter_prats");
  }
};

PlageConsultation.promptBackup();

CreneauConsultation = {
  /**
   * Open the modal to show the next available time slot
   *
   * @param chir_id
   * @param function_id
   * @param prise_rdv
   * @param only_func
   * @param rdv
   */
  modalPriseRDVTimeSlot: function (chir_id, function_id, prise_rdv, only_func, rdv) {
    // no chir, no function
    if (!chir_id && !function_id) {
      if (!alert($T("CPlageconsult-msg-You have not selected a practitioner or medical office"))) {
        return;
      }
    }

    new Url("dPcabinet", "vw_next_slots")
      .addParam("prat_id", chir_id)
      .addParam("function_id", function_id)
      .addParam("prise_rdv", prise_rdv)
      .addParam("only_func", only_func)
      .addParam("rdv", rdv)
      .requestModal("100%", "100%");
  },

  /**
   * Select All the lines
   */
  selectAllLines: function (classname, valeur) {
    $$('input.' + classname).each(function (elt) {
      elt.checked = valeur;
    });
  },

  /**
   * Time sleep
   */
  timeSleep: function () {
    var waitUntil = new Date().getTime() + 3 * 1000;
    while (new Date().getTime() < waitUntil) {
      true;
    }
  },

  /**
   * Update the next time slot
   *
   * @param function_id
   * @param week_number
   * @param count_plage
   * @param week_num
   * @param year
   * @param rdv
   */
  updateNextSlots: function (function_id, week_number, count_plage, week_num, year, rdv) {
    var oForm = getForm('selectNextSlots');

    // sélectionner plusieurs praticiens
    var get_prats_ids = $V(oForm.select("input.praticiens:checked"));
    var prats_ids = $A(get_prats_ids).join(',');

    // sélectionner plusieurs jours
    var get_days = $V(oForm.select("input.weekday:checked"));
    var days = $A(get_days).join(',');

    // sélectionner plusieurs heures
    var get_times = $V(oForm.select("input.timeday:checked"));
    var times = $A(get_times).join(',');

    // Récupère le libelle de la plage
    var libelle_plage = $V(oForm.plage_libelle);

    // message search
    var msg_nb_week = 0;
    var msg_year = year;

    if (week_number > 52) {
      msg_nb_week = week_number - 52;
      msg_year = parseInt(year) + 1;
    }
    else {
      msg_nb_week = week_number;
    }

    var tr = DOM.tr({className: 'tr_loading'}, DOM.td({colspan: "4"}, DOM.div({className: "loading"}, $T("CPlageconsult-msg-Research on the week n %s in %s", msg_nb_week, msg_year))));
    $("table_time_slot").insert(tr);

    var url = new Url("dPcabinet", "ajax_next_slots");
    url.addParam("prats_ids", prats_ids);
    url.addParam("function_id", function_id);
    url.addParam("days", days);
    url.addParam("times", times);
    url.addParam("libelle_plage", libelle_plage);
    url.addParam("week_number", week_number);
    url.addParam("week_num", week_num);
    url.addParam("year", year);
    url.requestJSON(function (slots) {

      CreneauConsultation.removeLoadingMessage();

      if ((get_prats_ids == null || get_days == null || get_times == null)) {
        var tr_no_slot = DOM.tr({class: ''},
          DOM.td({colspan: '4', className: 'empty'}, $T("CPlageconsult-msg-Please fill in the filters")));

        $("table_time_slot").insert(tr_no_slot);
        return false;
      }

      var next_week = slots["nb_week"];
      var year = slots["year"];

      //Suppression d'un élément d'un tableau :
      delete slots["nb_week"];
      delete slots["year"];

      if (week_num <= 52) {
        // compteur pour les 10 prochains creneaux
        var countMax = 10;
        var count = count_plage;
        var plage_libelle = "";

        // compte le nombre de semaine pour avoir max 52 semaines (1 an)
        var count_week_num = week_num;

        if (Object.keys(slots).length) {
          // Affiche le libellé du numéro de la semaine
          var msg_week_number = week_number;
          if (week_number > 52) {
            msg_week_number = week_number - 52;
          }

          var tr_week = DOM.tr({class: ''},
            DOM.th({colspan: '4', className: 'section'}, $T("CPlageconsult-Week number %s-court", msg_week_number)));

          $("table_time_slot").insert(tr_week);
          count_week_num++;

          // Création de la DOM
          for (var prat_key in slots) {
            for (var slot_key in slots[prat_key]) {
              var slot = slots[prat_key][slot_key];

              Object.keys(slot).each(function (datas) {

                if (count < countMax) {

                  // libellé de la plage
                  var libelle = slots[prat_key][slot_key][datas]["libelle_plage"];

                  if (libelle != plage_libelle && libelle) {
                    plage_libelle = slots[prat_key][slot_key][datas]["libelle_plage"];

                    var tr_week = DOM.tr({class: ''},
                      DOM.th({colspan: '4', className: 'category'}, $T("CPlageconsult-Wording of the time slot : %s", libelle)));

                    $("table_time_slot").insert(tr_week);
                  }

                  //traduction du jour et du mois pour avoir une date longue;
                  DateFormat.MONTH_NAMES = Control.DatePicker.Language['fr'].months;
                  DateFormat.DAY_NAMES = Control.DatePicker.Language['fr'].daysNames;

                  var day_date = DateFormat.format(Date.fromDATE(slots[prat_key][slot_key][datas]["date"]), "EE dd MMM yyyy");

                  var pratName = slots[prat_key][slot_key][datas]["praticien"];
                  var prat_id = slots[prat_key][slot_key][datas]["prat_id"];
                  var plage_id = slots[prat_key][slot_key][datas]["plage_id"];
                  var hour = slots[prat_key][slot_key][datas]["hour"];
                  var date_format_eu = Date.fromDATE(slots[prat_key][slot_key][datas]["date"]).toLocaleDate();

                  if (rdv == 1) {
                    var onclick = "Control.Modal.close(); CreneauConsultation.getDataForConsult('" + slots[prat_key][slot_key][datas]["date"] + "','" + date_format_eu + "', '" + hour + "', '" + plage_id + "');";
                  }
                  else {
                    var onclick = "Control.Modal.close(); CreneauConsultation.modalPriseRDV(0, '" + slots[prat_key][slot_key][datas]["date"] + "', '" + hour + "', '" + plage_id + "');";
                  }

                  var tr = DOM.tr({},
                    DOM.td({class: ''}, day_date),
                    DOM.td({class: ''}, hour),
                    DOM.td({
                        class:       '',
                        onmouseover: "ObjectTooltip.createEx(this, 'CMediusers-" + prat_id + "')"
                      }, pratName
                    ),
                    DOM.td({class: ''}, DOM.button({
                      'type':      'button',
                      'className': 'tick  notext',
                      'onclick':   onclick,
                      'title':     $T("common-action-Select")
                    }))
                  );

                  $("table_time_slot").insert(tr);
                }
                count++;
              });
            }
          }
          if (count < countMax) {
            CreneauConsultation.updateNextSlots(function_id, next_week, count, count_week_num, year, rdv);
          }
        }
        else if (count < countMax) {
          count_week_num++;
          CreneauConsultation.updateNextSlots(function_id, next_week, count, count_week_num, year, rdv);
        }
        else {
          var tr_no_slot = DOM.tr({class: ''},
            DOM.td({colspan: '4', className: 'empty'}, $T("CPlageconsult-No free slot")));

          $("table_time_slot").insert(tr_no_slot);
        }
      }
      else if (count_plage == 0 && week_num == 53) {
        var tr_no_slot = DOM.tr({class: ''},
          DOM.td({colspan: '4', className: 'empty'}, $T("CPlageconsult-No free slot")));

        $("table_time_slot").insert(tr_no_slot);
      }
    });
  },
  /**
   * Remove the loading message
   */
  removeLoadingMessage: function () {
    //supprimer message de chargement
    if ($("table_time_slot")) {
      $("table_time_slot").down('tr.tr_loading').remove();
    }
  },
  /**
   * Show list of the next slots
   *
   * @param function_id
   * @param week_number
   * @param rdv
   */
  showListNextSlots: function (function_id, week_number, rdv, year) {
    var url = new Url("cabinet", "vw_list_next_slots");

    url.requestUpdate("list_next_slots", {
      onComplete: function () {
        CreneauConsultation.updateNextSlots(function_id, week_number, 0, 1, year, rdv);
      }
    });
  },
  /**
   * Save a preference and refresh
   *
   * @param start_week_number
   * @returns {Boolean}
   */
  savePrefSlotAndReload: function (start_week_number) {
    var form = getForm("editPrefFreeSlot");
    $V(form.elements["pref[search_free_slot]"], start_week_number);
    return onSubmitFormAjax(form, function () {
      Control.Modal.refresh();
    });
  },
  /**
   * Open the modal rdv
   *
   * @param consult_id
   * @param date
   * @param heure
   * @param plage_id
   */
  modalPriseRDV: function (consult_id, date, heure, plage_id) {
    var url = new Url("dPcabinet", "edit_planning");
    url.addParam("dialog", 1);
    url.addParam("consultation_id", consult_id);
    url.addParam("date_planning", date);
    url.addParam("heure", heure);
    url.addParam("plageconsult_id", plage_id);
    url.modal({width: "100%", height: "100%", afterClose: window.refreshPlanning});
  },
  /**
   * Get datas for a consultation
   *
   * @param date
   * @param date2
   * @param heure
   * @param plage_id
   */
  getDataForConsult: function (date, date2, heure, plage_id) {
    $V(getForm('editFrm').heure, heure);
    $V(getForm('editFrm')._date_planning, date);
    $V(getForm('editFrm')._date, date2);
    $V(getForm('editFrm').plageconsult_id, plage_id);
    Control.Modal.close();
  }
};
