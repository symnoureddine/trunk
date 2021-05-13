/**
 * @package Mediboard\Pmsi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

Relance = {
  edit: function(relance_id, sejour_id, callback) {
    new Url("pmsi", "ajax_edit_relance")
      .addParam("relance_id", relance_id)
      .addParam("sejour_id", sejour_id)
      .requestModal("60%", "60%", {onClose: callback})
  },

  reloadButton: function(sejour_id) {
    if (!$("relance_button_" + sejour_id)) {
      return;
    }
    new Url("pmsi", "ajax_refresh_button_relance")
      .addParam("sejour_id", sejour_id)
      .requestUpdate("relance_button_" + sejour_id);
  },
  /**
   * Export
   */
  export: function() {
    var form = getForm("filterRelances");

    new Url("pmsi", "ajax_search_relances", "raw")
      .addFormData(form)
      .addParam('export', 1)
      .pop(400, 200, $T('Export'));
  },

  /**
   * Permet de trier la liste des relances
   *
   * @param button
   * @param nb_child
   */
  changeSort: function (button, nb_child) {
    const tbody = $('sorted_lines');
    const trList = tbody.select('tr');
    const sortFact = button.select('a')[0].hasClassName('sorted ASC') ? 1 : -1;
    const thead = $('title_lines');
    const thList = thead.select('th');
    if (nb_child === -1) {
      nb_child = thList.length;
    }
    trList.sort(
      function (trM, trP) {
        if (trM.down('td:nth-child(' + nb_child + ')').textContent.trim() < trP.down('td:nth-child(' + nb_child + ')').textContent.trim()) {
          return sortFact;
        }
        return sortFact * -1;
      }
    );
    trList.forEach(
      function (tr) {
        tbody.append(tr);
      }
    );
    thList.forEach(
      function (th) {
        if(th.select('a')[0]) {
          th.select('a')[0].removeClassName('sorted ASC');
          th.select('a')[0].removeClassName('sorted DESC');
          th.select('a')[0].addClassName('sortable');
        }
      }
    );
    if (sortFact === 1) {
      button.select('a')[0].removeClassName('sortable');
      button.select('a')[0].addClassName('sorted DESC');
    } else {
      button.select('a')[0].removeClassName('sortable');
      button.select('a')[0].addClassName('sorted ASC');
    }
  },

  /**
   * Recherche des relances
   */
  searchRelances: function () {
    getForm("filterRelances").onsubmit();
  },

  /**
   * Autocomplete praticien
   *
   * @param form
   */
  usersAutocomplete: function (form) {
    new Url("mediusers", "ajax_users_autocomplete")
      .addParam("praticiens", 1)
      .addParam("input_field", "chir_id_view")
      .autoComplete(form.chir_id_view, null, {
        minChars: 0,
        method: "get",
        select: "view",
        dropdown: true,
        afterUpdateElement: function(field, selected) {
          if ($V(form.chir_id_view) == "") {
            $V(form.chir_id_view, selected.down('.view').innerHTML);
          }
          var id = selected.getAttribute("id").split("-")[2];
          $V(form.chir_id, id);
        }
      });
  }
};
