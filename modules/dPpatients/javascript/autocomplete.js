/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

InseeFields = {
  initCPVille: function (sFormName, sFieldCP, sFieldCommune, sFieldINSEE, sFieldFocus) {
    var oForm = getForm(sFormName);

    // Populate div creation for CP
    var oField = oForm.elements[sFieldCP];

    if (oField) {
      // Autocomplete for CP
      new Url('patients', 'autocomplete_cp_commune')
        .addParam('column', 'code_postal')
        .addParam('name_input', sFieldCP)
        .autoComplete(oField, null, {
            width:         '250px',
            minChars:      2,
            updateElement: function (selected) {
              InseeFields.updateCPVille(selected, sFormName, sFieldCP, sFieldCommune, sFieldINSEE, sFieldFocus);
            }
          }
        );
    }

    // Populate div creation for Commune
    oField = oForm.elements[sFieldCommune];

    if (oField) {
      // Autocomplete for Commune
      new Url('patients', 'autocomplete_cp_commune')
        .addParam('column', 'commune')
        .addParam('name_input', sFieldCommune)
        .autoComplete(oField, null, {
            width:         "250px",
            minChars:      3,
            updateElement: function (selected) {
              InseeFields.updateCPVille(selected, sFormName, sFieldCP, sFieldCommune, sFieldINSEE, sFieldFocus);
            }
          }
        );
    }
  },

  updateCPVille: function (selected, sFormName, sFieldCP, sFieldCommune, sFieldINSEE, sFieldFocus) {
    var oForm = getForm(sFormName);
    var cp = selected.down(".cp");
    var commune = selected.down(".commune");
    var insee = selected.down('.insee');

    // Valuate CP and Commune
    if (sFieldCP) {
      $V(oForm.elements[sFieldCP], cp.getText().strip(), true);
    }

    $V(oForm.elements[sFieldCommune], commune.getText().strip(), true);

    if (sFieldINSEE) {
      $V(oForm.elements[sFieldINSEE], insee.getText().strip(), true);
    }

    // Give focus
    if (sFieldFocus) {
      $(oForm.elements[sFieldFocus]).tryFocus();
    }
  },

  initCSP: function (sFormName, sFieldCSP) {
    var oForm = getForm(sFormName);

    // Populate div creation for CSP
    var oField = oForm.elements[sFieldCSP];

    if (!oField) {
      return;
    }

    new Url('ppatients', 'ajax_csp_autocomplete')
      .autoComplete(oField, null, {
        width:              "250px",
        minChars:           3,
        dropdown:           true,
        afterUpdateElement: function (input, selected) {
          $V(oForm.csp, selected.get("id"));
        }
      });
  }
};

updateFields = function (selected, sFormName, sFieldFocus, sFirstField, sSecondField) {
  Element.cleanWhitespace(selected);
  var dn = selected.childNodes;
  $V(sFormName + '_' + sFirstField, dn[0].firstChild.firstChild.nodeValue, true);

  if (sSecondField) {
    $V(sFormName + '_' + sSecondField, dn[2].firstChild.nodeValue, true);
  }

  if (sFieldFocus) {
    $(sFormName + '_' + sFieldFocus).focus();
  }
};

initPaysField = function (sFormName, sFieldPays, sFieldFocus) {
  var sFieldId = sFormName + '_' + sFieldPays;
  var sCompleteId = sFieldPays + '_auto_complete';

  if (!$(sFieldId) || !$(sCompleteId)) {
    return;
  }

  new Ajax.Autocompleter(
    sFieldId,
    sCompleteId,
    "?m=patients&ajax=httpreq_do_pays_autocomplete&fieldpays=" + sFieldPays, {
      method:        'get',
      minChars:      2,
      frequency:     0.15,
      updateElement: function (element) {
        updateFields(element, sFormName, sFieldFocus, sFieldPays)
      }
    }
  );
};
