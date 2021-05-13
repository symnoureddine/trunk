/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

EchelleTri = {
  unique_id: null,
  chir_id: null,
  searchMotif: function() {
    var form = getForm("editRPUtri");
    var url = new Url("urgences", "vw_search_motif");
    url.addParam('rpu_id'       , form.rpu_id.value);
    url.requestModal(600, 600);
  },
  requestInfoPatTri: function() {
    var oForm = getForm("editRPUtri");
    var iPatient_id = $V(oForm._patient_id);
    if(!iPatient_id){
      return false;
    }
    var url = new Url("dPpatients", "httpreq_get_last_refs");
    url.addParam("patient_id" , iPatient_id);
    url.addParam("is_anesth"  , 0);
    url.requestUpdate("infoPat");
    return true;
  },
  refreshConstantesMedicalesTri: function(context_guid) {
    if (context_guid) {
      var oForm = getForm("editRPUtri");
      var iPatient_id = $V(oForm._patient_id);
      var url = new Url('patients' , 'httpreq_vw_form_constantes_medicales');
      url.addParam("context_guid", context_guid);
      url.addParam("patient_id"   , iPatient_id);
      url.addParam('display_graph', 0);
      url.addParam('unique_id', EchelleTri.unique_id);
      url.requestUpdate('constantes-tri');
      if (getForm("edit-constantes-medicales{{$unique_id}}") && window.toggleAllGraphs) {
        toggleAllGraphs();
      }
    }
  },
  refreshAntecedentsPatient: function() {
    var oForm = getForm("editRPUtri");
    var iPatient_id = $V(oForm._patient_id);
    var url = new Url("dPcabinet", "httpreq_vw_list_antecedents");
    url.addParam("patient_id" , iPatient_id);
    url.addParam("_is_anesth" , "0");
    url.addParam("current_m"  , "dPurgences");
    url.addParam("sejour_id"  , "");
    url.addParam("chir_id"    , EchelleTri.chir_id);
    url.addParam("addform"    , "tri");
    url.requestUpdate('antecedentsanesth');
  },
  onChangePatient: function() {
    EchelleTri.requestInfoPatTri();
    EchelleTri.refreshAntecedentsPatient();
    EchelleTri.refreshConstantesMedicalesTri();
  },
  showBttsValidation: function(can_validate, can_invalidate) {
    $('echelle_tri_valided').style.display = (can_validate == '1') ? '' : 'none';
    $('echelle_tri_invalided').style.display = (can_invalidate == '1') ? '' : 'none';
  }
};