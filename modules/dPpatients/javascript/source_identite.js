/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

SourceIdentite = {
  patient_id: null,
  copying_prenom: false,
  callback: null,
  traits_stricts: [
    'nom_jeune_fille', 'prenom', 'prenoms',
    'naissance', 'sexe', '_pays_naissance_insee', 'lieu_naissance', 'cp_naissance',
    '_ins_type'
  ],

  /**
   *
   */
  openList: function() {
    new Url('patients', 'vw_sources_identite')
      .addParam('patient_id', this.patient_id)
      .requestModal(1000, 700);
  },

  /**
   * Affiche les INS récupérés depuis le téléservice
   * @param source_identite_id
   */
  showINS: function(source_identite_id) {
    new Url('patients', 'ajax_list_ins')
      .addParam('source_identite_id', source_identite_id)
      .requestModal(500, 500);
  },

  /**
   * Rafraîchit la liste des sources d'identité
   */
  refreshList: function() {
    new Url('patients', 'ajax_list_sources_identite')
      .addParam('patient_id', SourceIdentite.patient_id)
      .requestUpdate('sources_patient_area');
  },

  /**
   * Affichage de la widget d'upload de fichier si un type de justificatif est sélectionné
   *
   * @param select
   */
  toggleFile: function(select) {
    select.form.select('.justificatif_file').invoke($V(select) ? 'show' : 'hide');
  },

  /**
   * Vérification du formulaire avant enregistrement de la source d'identité
   *
   * @param form
   * @returns {Boolean|boolean}
   */
  onSubmit: function (form) {
    return onSubmitFormAjax(form, {
      onComplete: (function () {
        Control.Modal.close();

        if (this.callback) {
          this.callback();
        }
      }).bind(this)
    });
  },

  copyPrenom: function(element) {
    if (SourceIdentite.copying_prenom) {
      return;
    }

    SourceIdentite.copying_prenom = true;

    var split_prenoms = $V(element.form.prenoms).split(' ');

    switch (element.name) {
      default:
      case 'prenom_naissance':
        $V(element.form.prenoms, $V(element));

        // Retrait de la première entrée de la liste des prénoms
        split_prenoms.shift();

        // Ajout des autres prénoms à la liste
        if (split_prenoms.length) {
          $V(element.form.prenoms, $V(element.form.prenoms) + ' ' + split_prenoms.join(' '));
        }

        break;

      case 'prenoms':
        if (split_prenoms.length) {
          $V(element.form.prenom_naissance, split_prenoms[0]);
        }
    }

    SourceIdentite.copying_prenom = false;
  },

  copyData: function(data) {
    var form = getForm('editFrm');

    if (!form) {
      return;
    }

    $V(form._mode_obtention, 'insi');
    $V(form._previous_ins, JSON.stringify(data._previous_ins));
    $V(form._map_source_form_fields, 1);
    $V(form._force_manual_source, 0);

    Object.keys(data).each(function (_key) {
      if (form.elements[_key]) {
        $V(form.elements[_key], data[_key]);
      }
    });

    Control.Modal.close();

    form.onsubmit();
  },

  retrogateStatus: function() {
    if (!confirm($T('CSourceIdentite-Validate retrograde status'))) {
      return;
    }

    new Url('patients', 'do_retrograde_status', 'dosql')
      .addParam('patient_id', this.patient_id)
      .requestUpdate('systemMsg', {method: 'POST', onComplete: function() {document.location.reload(); } });
  }
};
