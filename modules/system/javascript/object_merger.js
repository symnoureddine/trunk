/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

ObjectMerger = {
  setField: function (field, source) {
    var form = source.form;
    var value = $V(source);
    var field = $(form.elements[field]);

    // Update Value
    $V(field, value);

    // Also check the source when clicking
    if (source.type == 'radio') {
      source.checked = true;
    }

    if (!field.hasClassName) {
      return;
    }

    var view = null;
    var props = field.getProperties();

    // Can't we use Calendar.js helpers ???
    if (props.dateTime) {
      view = $(form.elements[field.name + '_da']);
      $V(view, value ? Date.fromDATETIME(value).toLocaleDateTime() : "");
    }

    if (props.date) {
      view = $(form.elements[field.name + '_da']);
      $V(view, value ? Date.fromDATE(value).toLocaleDate() : "");
    }

    if (props.time) {
      view = $(form.elements[field.name + '_da']);
      $V(view, value);
    }

    var label = Element.getLabel(source);
    if (props.ref) {
      view = $(form.elements["_" + field.name + '_view']);
      $V(view, label.getText().strip());
    }

    if (props.mask) {
      $V(field, label.getText().strip(), false);
    }
  },

  updateOptions: function (field) {
    var form = field.form;
    $A(form.elements["_choix_" + field.name]).each(function (element) {
      element.checked = element.value.stripAll() == field.value.stripAll();
    });
  },

  confirm: function (fast) {
    $V(getForm("form-merge").fast, fast);
    Modal.confirm($('confirm-' + fast), {onOK: ObjectMerger.perform});
    return false;
  },

  perform: function () {
    getForm("form-merge").submit();
  }
};
