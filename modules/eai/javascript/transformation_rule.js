/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Transformation rule
 */
EAITransformationRule = {
  modal          : null,
  action_type_selected : "",

  edit: function(transformation_rule_id, transformation_rule_sequence_id) {
    new Url("eai", "ajax_edit_transformation_rule")
      .addParam("transformation_rule_id", transformation_rule_id)
      .addParam("transformation_rule_sequence_id", transformation_rule_sequence_id)
      .requestModal('80%', '80%');
  },

  stats: function(transformation_rule_id) {
    new Url("eai", "ajax_show_stats_transformations")
      .addParam("transformation_rule_id", transformation_rule_id)
      .requestModal(600);
  },

  onSubmit: function(form) {
    return onSubmitFormAjax(form, Control.Modal.close);
  },

  moveRowUp: function(row) {
    if (row.previous() === row.up().childElements()[1]) {
      return;
    }

    row.previous().insert({before: row});
  },

  moveRowDown: function(row) {
    if (row.next()) {
      row.next().insert({after: row});
    }
  },

  target: function (transf_rule_sequence_id, target) {
    EAITransformationRule.modal = new Url("eai", "ajax_target_transformation_rule")
      .addParam("transf_rule_sequence_id",transf_rule_sequence_id)
      .addParam("target", target)
      .requestModal("90%");
  },

  refreshTarget: function(components, target) {
    var container = $("EAITransformationRule-"+target);
    components.split("|").each(function(value) {
      if(document.getElementById(target+"_"+value) === null){
        container.insert(DOM.span({
          id: target+"_"+value,className:'circled', onclick:"EAITransformationRule.deleteTarget('" + value + "','" + target + "');"
        }, value));
      }
    });

    var form = getForm('editEAITransformationRule');
    var input = form.elements[target];

    if (input.value) {
      form.elements[target].setAttribute('value', input.value + '|' + components);
    }
    else {
      form.elements[target].setAttribute('value', components);
    }
  },

  deleteTarget: function(component,target) {
    var container = $("EAITransformationRule-"+target);

    var toRemove = document.getElementById(target+"_"+component);

    container.removeChild(toRemove);

    var form = getForm('editEAITransformationRule');
    var input = form.elements[target];

    // Suppression du component dans la chaine
    var new_value = input.value.replace(component, '');
    // Suprresion de deux pipes consécutifs si on a supprimé un component en milieu de chaine
    new_value = new_value.replace('||', '|');

    // Si premier caractère est un '|' on l'enlève pour avoir une chaine propre
    var first_char = new_value.substring(0, 1);
    if (first_char === '|') {
      new_value = new_value.substring(1);
    }

    // Si dernier caractère est un '|' on l'enlève pour avoir une chaine propre
    var last_char = new_value.substr(new_value.length - 1);
    if (last_char === '|') {
      new_value = new_value.slice(0, -1);
    }

    form.elements[target].setAttribute('value', new_value);
  },

  emptyParamsValue: function() {
    document.getElementById("editEAITransformationRule_params").value = "";
    var paramsSerial  = document.getElementById("paramsSerialize");
    paramsSerial.innerHTML = '';
    paramsSerial.removeClassName('circled');
  },

  actionSelect: function(action_type) {
    EAITransformationRule.action_type_selected = action_type;
    this.hideParamsForm();
    var paramsRow     = document.getElementById("paramsRow");
    var xpathTarget   = document.getElementById("xPathTargetRow");
    var paramsSerial  = document.getElementById("paramsSerialize");
    switch (action_type) {
      case 'delete':
        // XPath Target Null
        xpathTarget.hidden  = true;
        paramsRow.hidden    = true;
        break;
      case 'insert':
      case 'sub':
      case 'trim':
      case 'pad':
      case 'map':
        //Params Not Null
        paramsRow.hidden    = false;
        xpathTarget.hidden  = false;
        this.editParams();
        break;
      default:
        paramsRow.hidden    = true;
        xpathTarget.hidden  = false;
    }
  },

  editParams: function () {
    EAITransformationRule.modal = new Url("eai", "ajax_edit_params_rule")
      .addParam("action_type",EAITransformationRule.action_type_selected)
      .requestUpdate('paramsEdit');
  },

  hideParamsForm: function() {
    var paramsEdit = document.getElementById("paramsEdit");
    paramsEdit.innerHTML = "";
  },

  serializeParams: function () {
    var params        = document.getElementById("editEAITransformationRule_params");
    var paramsSerial  = document.getElementById("paramsSerialize");
    var inputs        = document.getElementById("paramsEdit").getElementsByClassName('actionParams');
    // Validation de la saisie des paramètres
    if(inputs["param1"].value !== "") {
      if(inputs["param1"].readAttribute('type') === "text") {
        params.value = '"'+inputs["param1"].value+'"';
      }
      else {
        params.value = inputs["param1"].value;
      }
    }
    else {
      alert($T('CTransformationRule.params.error'));
      return false;
    }
    if(inputs["param2"] !== undefined) {
      if(inputs["param2"].value !== "") {
        if(inputs["param2"].readAttribute('type') === "text") {
          params.value += ',"'+inputs["param2"].value+'"';
        }
        else {
          params.value += ','+inputs["param2"].value;
        }
      }
      else {
        alert($T('CTransformationRule.params.error'));
        return false;
      }
    }
    // Paramètre 3 présent uniquement sur l'action "Pad" et de type select - A modifier si nouvelle action avec param3 de type input
    if(inputs["param3"] !== undefined) {
      params.value += ','+inputs["param3"].value;
    }
    paramsSerial.innerHTML = '<p>'+params.value+'</p>';
    paramsSerial.addClassName('circled');
    return true;
  },

  unserialize: function () {

    // On désérialise la valeur du champs "params"
    var inputs      = document.getElementById("paramsEdit").getElementsByClassName('actionParams');
    var paramsValue = $V(getForm('editEAITransformationRule').elements['params']);

    // On enlève les quotes sur les chaînes de caractères et on sépare les paramètres
    var params        = paramsValue.replace(/"/g,'');
    var unserialized  = params.split(',');

    // On place les éléments désérialisés dans les champs respectifs
    unserialized.forEach(function(element,index) {
      inputs["param"+(index+1)].value = element;
    });
  },

  apply : function (rule_id) {
    new Url('eai', 'ajax_apply_rule')
      .addParam('rule_id', rule_id)
      .requestUpdate('rule_' + rule_id);
  },

  toggleDisabled : function (input_name) {
    var form = getForm('editEAITransformationRule');
    var input = form.elements[input_name];
    input.disabled ? input.disabled = '' : input.disabled = 'disabled';
  }
}
