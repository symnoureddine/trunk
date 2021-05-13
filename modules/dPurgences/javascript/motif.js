/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

Chapitre = {
  edit: function(chapitre_id) {
    new Url('urgences', 'ajax_edit_chapitre_motif')
      .addParam('chapitre_id', chapitre_id)
      .requestModal(400);
  },
  onCloseModal: function() {
    Control.Modal.close();
    Chapitre.refreshList();
    Motif.refreshList();
  },
  onSubmit: function(form) {
    return onSubmitFormAjax(form, Chapitre.onCloseModal);
  },
  confirmDeletion: function(form) {
    var options = {
      typeName:'chapitre', 
      objName: $V(form.nom),
      ajax: 1
    };
    confirmDeletion(form, options, Chapitre.onCloseModal);
  },
  refreshList: function() {
    new Url('urgences', 'vw_motifs')
      .addParam('liste', 'chapitre')
      .requestUpdate('chapitres');
  }
};

Motif= {
  readonly_echelle_tri: null,
  edit: function(motif_id, readonly, modale) {
    var url = new Url('urgences', 'ajax_edit_chapitre_motif');
    url.addParam('motif_id', motif_id);
    if (!Object.isUndefined(readonly)) {
      url.addParam('readonly', readonly);
    }
    if (!Object.isUndefined(modale)) {
      url.addParam('see_questions', 0)
        .requestUpdate('view_motif_rpu');
    }
    else {
      if (!Object.isUndefined(readonly)) {
        url.requestModal(null, '750');
      }
      else {
        url.requestModal(800, '750');
      }
    }
  },
  onCloseModal: function() {
    Motif.refreshList();
    Control.Modal.close();
  },
  onSubmit: function(form) {
    return onSubmitFormAjax(form, Motif.onCloseModal);
  },
  confirmDeletion: function(form) {
    var options = {
      typeName:'motif',
      objName: $V(form.nom),
      ajax: 1
    };
    confirmDeletion(form, options,  Motif.onCloseModal);
  },
  refreshList: function() {
    new Url('urgences', 'vw_motifs')
      .addParam('liste', 'motif')
      .requestUpdate('motifs');
  },
  searchMotif: function() {
    var form = getForm('searchMotif');
    new Url('urgences', 'vw_search_motif')
      .addParam('reload', 1)
      .addFormData(form)
      .requestUpdate('reload_search_motif');
    return false;
  },
  refreshComplement: function() {
    var form = getForm('editRPUtri');
    new Url('urgences', 'ajax_form_complement')
      .addParam('rpu_id', form.rpu_id.value)
      .requestUpdate('form-edit-complement');
  },
  selectDiag: function(code_diag, motif_id) {
    var form = getForm('choiceMotifRPU');
    $V(form.code_diag, code_diag);
    return onSubmitFormAjax(form, {
      onComplete: function() {
        Control.Modal.close();
        Motif.refreshComplement();
        Motif.loadQuestionsRpu();
        Motif.edit(motif_id, 1, true);
        $('view_motif_rpu').show();
      }
    });
  },
  deleteDiag: function(form, see_reload) {
    return onSubmitFormAjax(form, {
      onComplete: function() {
        if (see_reload && !form.echelle_tri_id.value) {
          Motif.reloadComplementEchelle(form);
        }
        Motif.refreshComplement();
        Motif.loadQuestionsRpu();
        $('view_motif_rpu').hide();
      }
    });
  },
  changeCCMU: function(form) {
    var form_echelle = getForm('modifCcmuManuel');
    if ($V(form_echelle.last_ccmu_manuel) == 0) {
      onSubmitFormAjax(form_echelle);
    }
    return onSubmitFormAjax(form, {
      onComplete: function() {
        Motif.refreshComplement();
        Motif.loadQuestionsRpu();
        $('view_motif_rpu').hide();
      }
    });
  },
  loadQuestionsRpu: function(just_validation) {
    var form = getForm('editRPUtri');
    new Url('urgences', 'ajax_form_questions_motif')
      .addParam('rpu_id'       , form.rpu_id.value)
      .addParam('just_validation', just_validation ? 1 : 0)
      .requestUpdate(just_validation ? 'refresh_validation_echelle_tri' : 'form-question_motif');
  },
  submitReponse: function(form) {
    return onSubmitFormAjax(form, {
      onComplete: function() {
        Motif.loadQuestionsRpu(true);
        Motif.refreshComplement();
      }
    });
  },
  seeTraitements: function(form) {
    return onSubmitFormAjax(form, {
      onComplete: function() {
        if (!form.echelle_tri_id.value) {
          Motif.reloadComplementEchelle(form);
        }
        form.antidiabetique.hidden = 'hidden';
        form.anticoagulant.hidden = 'hidden';
        if (form.antidiabet_use.value == 'oui') {
          form.antidiabetique.hidden = '';
        }
        if (form.anticoagul_use.value == 'oui') {
          form.anticoagulant.hidden = '';
        }
      }
    });
  },
  reloadComplementEchelle: function(form) {
    new Url('urgences', 'ajax_echelle_tri')
      .addParam('rpu_id', form.rpu_id.value)
      .requestUpdate('form-echelle_tri');
  },
  setReactivite: function(cote, new_value) {
    if (Motif.readonly_echelle_tri == '1') {
      return;
    }
    var form = getForm('formEchelleTri');
    var value_change = new_value;
    if (cote == 'reactivite_gauche') {
      if ($V(form.reactivite_gauche) == new_value) {
        value_change = '';
      }
      $V(form.reactivite_gauche, value_change);
    }
    else {
      if ($V(form.reactivite_droite) == value_change) {
        value_change = '';
      }
      $V(form.reactivite_droite, value_change);
    }
    return onSubmitFormAjax(form, {
      onComplete: function() {
        $(cote+'_reactif').setStyle({'font-weight': 'normal', 'color': 'black'});
        $(cote+'_non_reactif').setStyle({'font-weight': 'normal', 'color': 'black'});
        if (new_value == value_change) {
          $(cote+'_'+new_value).setStyle({'font-weight': 'bold', 'color': 'red'});
        }
        if (!form.echelle_tri_id.value) {
          Motif.reloadComplementEchelle(form);
        }
        Motif.refreshComplement();
      }
    });
  },

  setPupilles: function(cote, add) {
    if (Motif.readonly_echelle_tri == '1') {
      return;
    }
    form = getForm('formEchelleTri');
    var niveau = form.pupille_droite.value;
    if (cote == 'pupille_gauche') {
      niveau = form.pupille_gauche.value;
    }

    if (add == 0) {
      niveau = niveau-1;
      if (niveau == -1) niveau = 3;
    }
    var new_niveau = 0;
    switch (parseInt(niveau)) {
      case 0:
        new_niveau = 1;
        $(cote+'_circle').style.border = '2px solid black';
        $(cote+'_circle').style.margin = '8px';
        break;
      case 1:
        new_niveau = 2;
        $(cote+'_circle').style.border = '5px solid black';
        $(cote+'_circle').style.margin = '5px';
        break;
      case 2:
        new_niveau = 3;
        $(cote+'_circle').style.border = '8px solid black';
        $(cote+'_circle').style.margin = '2px';
        break;
      default :
        new_niveau = 0;
        $(cote+'_circle').style.border = '0px solid black';
        $(cote+'_circle').style.margin = '1px';
        break;
    }
    if (add) {
      if (cote == 'pupille_gauche') {
        $V(form.pupille_gauche, new_niveau);
      }
      else {
        $V(form.pupille_droite, new_niveau);
      }
      Motif.deleteDiag(form, 1);
    }
  },
  saveGlasgow: function(form, context_guid) {
    return onSubmitFormAjax(form, {
      onComplete: function() {
        Motif.refreshComplement();
        EchelleTri.refreshConstantesMedicalesTri(context_guid);
        refreshConstantesMedicales(context_guid);
      }
    });
  },
  seeSA: function(form, see_reload) {
    if ($V(form.enceinte) == 1) {
      $('see_semaine_grossesse').show();
    }
    else {
      $('see_semaine_grossesse').hide();
      $V(form.semaine_grossesse, '');
    }
    Motif.deleteDiag(form, see_reload);
  }
};

Question = {
  edit: function(question_id, motif_id) {
    var url = new Url('urgences', 'ajax_edit_question_motif');
    url.addParam('question_id', question_id);
    if (!Object.isUndefined(motif_id)) {
      url.addParam('motif_id', motif_id);
    }
    url.requestModal(800);
  },
  onCloseModal: function() {
    Control.Modal.close();
    Control.Modal.refresh();
  },
  onSubmit: function(form) {
    return onSubmitFormAjax(form, Question.onCloseModal);
  },
  remove: function(question_id, nom){
    var form = getForm('question-delete');
    form.question_id.value = question_id;
    Question.confirmDeletion(form, nom);
  },
  confirmDeletion: function(form, nom) {
    var options = {
      typeName:'question',
      objName: nom,
      ajax: 1
    };
    confirmDeletion(form, options, Control.Modal.refresh.curry());
  }
};
