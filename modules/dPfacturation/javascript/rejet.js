/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

Rejet = {
  modal: null,
  refreshList: function(form) {
    var url = new Url('facturation', 'vw_rejects_xml');
    url.addParam('list'       , 1);
    url.addFormData(form);
    url.requestUpdate("list_rejets_xml_chir");
  },
  traitementXML: function(chir_id) {
    var url = new Url('facturation', 'vw_rejects_xml');
    url.addParam('chir_id'    , chir_id);
    url.addParam('traitement' , 1);
    url.addParam('list'       , 1);
    url.requestUpdate("list_rejets_xml_chir");
  },
  searchFactureRejet: function(form) {
    var url = new Url('facturation', 'vw_rejets_facture');
    url.addFormData(form);
    url.requestUpdate("list_rejets_facture");
  },
  traiterRejet: function(form) {
    onSubmitFormAjax(form, {
      onComplete : function() {Rejet.searchFactureRejet(getForm("choice_rejet_all"));}
    });
  }
};