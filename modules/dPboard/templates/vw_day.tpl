{{*
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=cabinet script=edit_consultation}}
{{mb_script module=planningOp script=operation}}

{{if "planSoins"|module_active}}
  {{mb_script module=planSoins script=plan_soins}}
{{/if}}
{{mb_script module=soins script=soins}}

{{mb_script module=compteRendu script=document}}
{{mb_script module=system script=alert}}


{{if "dPprescription"|module_active}}
  {{mb_script module=prescription script=prescription}}
  {{mb_script module=prescription script=element_selector}}
{{/if}}

{{if "dPmedicament"|module_active}}
  {{mb_script module=medicament script=medicament_selector}}
  {{mb_script module=medicament script=equivalent_selector}}
{{/if}}

{{if "messagerie"|module_active}}
  {{mb_script module=messagerie script=UserEmail}}
{{/if}}

{{if "dPImeds"|module_active}}
  {{mb_script module=Imeds script=Imeds_results_watcher}}
{{/if}}

<script>
  Consultation.useModal();
  Operation.useModal();

  updateListConsults = function(withClosed) {
    new Url('cabinet', 'httpreq_vw_list_consult')
      .addParam("chirSel"    , "{{$prat->_id}}")
      .addParam("functionSel", "{{$function->_id}}")
      .addParam("date"       , "{{$date}}")
      .addParam("vue2"       , "{{$vue}}")
      .addParam("selConsult" , "")
      .addParam("board"      , "1")
      .addParam('withClosed' , withClosed)
      .requestUpdate("tab-consultations");
  };

  initUpdateListConsults = function() {
    updateListConsults();
    new PeriodicalExecuter(updateListConsults, 120);
  };

  updateListPrescriptions = function () {
    new Url('dPboard', 'httpreq_vw_list_patient_with_prescription')
      .addParam('pratSel', '{{$prat->_id}}')
      .addParam('functionSel', '{{$function->_id}}')
      .addParam('date', '{{$date}}')
      .addParam('board', '1')
      .requestUpdate("tab-autre-responsable");
  };

  initUpdateListPrescriptions = function () {
    updateListPrescriptions();
    new PeriodicalExecuter(updateListPrescriptions, 120);
  };

  updateListOperations = function() {
    new Url('planningOp', 'httpreq_vw_list_operations')
      .addParam('pratSel'    , '{{$prat->_id}}')
      .addParam('functionSel', '{{$function->_id}}')
      .addParam('date'       , '{{$date}}')
      .addParam('urgences'   , '0')
      .addParam('board'      , '1')
      .requestUpdate("tab-operations");
  };

  initUpdateListOperations = function() {
    updateListOperations();
    new PeriodicalExecuter(updateListOperations, 120);
  };

  updateListHospi = function() {
    new Url('board', 'httpreq_vw_hospi')
      .addParam('chirSel'    , '{{$prat->_id}}')
      .addParam('functionSel', '{{$function->_id}}')
      .addParam('date'       , '{{$date}}')
      .requestUpdate('tab-hospitalisations');
  };

  /**
   * Update the list of canceled surgeries
   */
  updateCanceledSurgeries = function() {
    new Url('board', 'ajax_list_canceled_surgeries')
      .addParam('practitioner_id', '{{$prat->_id}}')
      .addParam('function_id', '{{$function->_id}}')
      .addParam('date', '{{$date}}')
      .requestUpdate('tab-canceled-operations');
  };

  updateWorkList = function() {
    new Url('board', 'ajax_worklist')
      .addParam('chirSel' , '{{$prat->_id}}')
      .addParam('date'    , '{{$date}}')
      .requestUpdate('worklist');
  };

  showDossierSoins = function(sejour_id, date, default_tab) {
    var url = new Url('soins', 'ajax_vw_dossier_sejour');
    url.addParam('sejour_id', sejour_id);
    url.addParam('modal', '1');
    if (default_tab) {
      url.addParam('default_tab', default_tab);
    }
    url.requestModal('100%', '100%', {
      onClose : function() {
        TabsPrescription.updatePrescriptions();
        TabsPrescription.updateInscriptions();
        TabsPrescription.updateAntibios();
        TabsPrescription.updateComPharma();
        refreshLineSejour(sejour_id);
      }
    });
    modalWindow = url.modalObject;
  };

  refreshLineSejour = function(sejour_id) {
    new Url('soins', 'vw_sejours')
      .addParam('sejour_id', sejour_id)
      .addParam('lite_view', true)
      .addParam('service_id', "")
      .addParam('show_affectation', true)
      .addParam('show_full_affectation', true)
      .addParam('board', true)
      .requestUpdate('line_sejour_'+sejour_id, {onComplete: function() {
      {{if "dPImeds"|module_active}}
      ImedsResultsWatcher.loadResults();
      {{/if}}
    } });
  };

  Main.add(function () {
    tabsEvents = Control.Tabs.create('tabs-prat-events', true);
    var prat_events = $('prat_events');
    ViewPort.SetAvlHeight(prat_events, 0.5);
    {{if $prat->_id || $function->_id}}
      initUpdateListConsults();
      initUpdateListPrescriptions();
      initUpdateListOperations();
      updateListHospi();
      updateCanceledSurgeries();
      updateWorkList();
    {{/if}}
    $('tab-consultations').fixedTableHeaders({container : prat_events, refTop : tabsEvents.activeContainer});
    $('tab-operations').fixedTableHeaders({container : prat_events, refTop : tabsEvents.activeContainer});
    $('tab-canceled-operations').fixedTableHeaders({container : prat_events, refTop : tabsEvents.activeContainer});
    $('tab-hospitalisations').fixedTableHeaders({container : prat_events, refTop : tabsEvents.activeContainer});
    ViewPort.SetAvlHeight('worklist', 1.0);
    Calendar.regField(getForm('changeDate').date, null, {noView: true});
  });
</script>

<table class="main">
  <tr>
    <th colspan="2">
      <a id="vw_day_date_a" href="?m={{$m}}&tab={{$tab}}&date={{$prec}}">&lt;&lt;&lt;</a>
      <form name="changeDate" action="?m={{$m}}" method="get">
        <input type="hidden" name="m" value="{{$m}}" />
        <input type="hidden" name="tab" value="{{$tab}}" />
        {{$date|date_format:$conf.longdate}}
        <input type="hidden" name="date" class="date" value="{{$date}}" onchange="this.form.submit();" />
      </form>
      <a href="?m={{$m}}&tab={{$tab}}&date={{$suiv}}">&gt;&gt;&gt;</a>
    </th>
  </tr>
</table>

{{if "doctolib"|module_active && "doctolib staple_authentification client_access_key_id"|gconf}}
  {{mb_include module=doctolib template=buttons/inc_vw_buttons_appointments}}
{{/if}}

<!--  Consultations / Operations / Hospitalisations-->
<fieldset class="me-align-auto me-margin-bottom-8 me-margin-top-8 me-padding-top-0">
  <div id="prat_events">
    <div>
      <ul class="control_tabs me-margin-top-0 me-small" id="tabs-prat-events">
        <li><a href="#tab-hospitalisations" class="empty">{{tr}}CSejour|pl{{/tr}} <small>(&ndash;)</small></a></li>
        <li><a href="#tab-consultations" class="empty">{{tr}}CConsultation|pl{{/tr}} <small>(&ndash;)</small></a></li>
        <li><a href="#tab-operations" class="empty">{{tr}}COperation|pl{{/tr}} <small>(&ndash;)</small></a></li>
        <li><a href="#tab-canceled-operations" class="empty">{{tr}}COperation-annulee|pl{{/tr}} <small>(&ndash;)</small></a></li>
        <li title="{{tr}}CSejour-other-in-charge-desc{{/tr}}"><a href="#tab-autre-responsable" class="empty">{{tr}}CSejour-other-in-charge{{/tr}} <small>(&ndash;)</small></a></li>
      </ul>
    </div>

    <div id="tab-hospitalisations" style="display: none;overflow: auto;" class="me-no-align"></div>
    <div id="tab-consultations" style="display: none;overflow: auto;" class="me-no-align"></div>
    <div id="tab-operations" style="display: none;overflow: auto;" class="me-no-align"></div>
    <div id="tab-canceled-operations" style="display: none;overflow: auto;" class="me-no-align"></div>
    <div id="tab-autre-responsable" style="display: none;overflow: auto;" class="me-no-align"></div>
  </div>
</fieldset>

<!-- Volet des worklists -->
<fieldset class="me-align-auto me-padding-top-0">
  <div id="worklist" style="overflow: auto"></div>
</fieldset>
