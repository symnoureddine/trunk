/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

Board = {
  onSelectFilter: function(field) {
    if (field.name == 'praticien_id') {
      if (field.form.elements['function_id']) {
        $V(field.form.elements['_function_view'], '', false);
        $V(field.form.elements['function_id'], 0, false);
      }
    }
    else {
      $V(field.form.elements['_chir_view'], '', false);
      $V(field.form.elements['praticien_id'], 0, false);
    }

    field.form.submit();
  },
  /**
   * Remplis les champ begin_date et end_date pour le filtre sur les interventions non cotées (saisie des codages)
   *
   * @param period_start
   * @param period_end
   */
  setPeriod: function (period_start, period_end) {
    var form = getForm('filterObjects'),
      debut = form.begin_date,
      debut_da = form.begin_date_da,
      fin = form.end_date,
      fin_da = form.end_date_da;
    // On n'utilise pas $V() sur 'debut' et 'fin' pour ne pas déclencher l'event "onchange"
    debut.value = period_start;
    fin.value = period_end;
    $V(debut_da, Date.fromDATE(period_start).toLocaleDate());
    $V(fin_da, Date.fromDATE(period_end).toLocaleDate());
  },
  /**
   *  Période personnalisée pour les filtres des dates
   */
  customPeriod: function (debutChanged) {
    var form = getForm('filterObjects'),
      debut_da = form.begin_date_da,
      fin_da = form.end_date_da,
      debut = form.begin_date,
      fin = form.end_date;
    // Décoche les cases de filtres prédéfinis
    form.select_days[0].checked = false;
    form.select_days[1].checked = false;
    form.select_days[2].checked = false;
    form.select_days[3].checked = false;
    // On vérifie que le début est plus grand que la fin
    if (debut.value < fin.value) {
      return;
    }
    // Sinon la plus grande valeur est utilisée dans les deux champs
    if (debutChanged) {
      fin.value = debut.value;
      fin_da.value = Date.fromDATE(fin.value).toLocaleDate();
    } else {
      debut.value = fin.value;
      debut_da.value = Date.fromDATE(debut.value).toLocaleDate();
    }
  }

};
