{{*
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if "dPpmsi"|module_active}}
  {{mb_script module=pmsi script=relance ajax=true}}
{{/if}}

<script>
  var frequency = 300;

  showPrescriptions = function() {
    new Url('board', 'ajax_tabs_prescription')
      .addParam('chirSel' , '{{$chirSel}}')
      .addParam('date', '{{$date}}')
      .requestUpdate('prescriptions');
  };

  updateMessagerie = function() {
    new Url('messagerie', 'ajax_list_mails')
      .addParam('account_id', '{{$account->_id}}')
      .addParam('mode', 'all')
      .periodicalUpdate('messagerie', {
        frequency : frequency,
        method: 'get',
        onComplete: function() {
          if ($$('#messagerie tr').length <= 2) {
            $('tab_messagerie').addClassName('empty');
          }
        }
      });
  };

  initUpdateActes = function() {
    new Url('board', 'ajax_list_interv_non_cotees')
      .addParam('praticien_id', '{{$chirSel}}')
      .addParam('end_date', '{{$date}}')
      .addParam('board'       , '1')
      .periodicalUpdate('actes_non_cotes', { frequency: frequency } );
  };

  updateActes = function() {
    new Url('board', 'ajax_list_interv_non_cotees')
      .addParam('praticien_id', '{{$chirSel}}')
      .addParam('end_date', '{{$date}}')
      .addParam('board'       , '1')
      .requestUpdate('actes_non_cotes');
  };

  initUpdateRelances = function() {
    new Url('pmsi', 'ajax_vw_relances')
      .addParam('chir_id', '{{$chirSel}}')
      .periodicalUpdate('relances', {frequency: frequency});
  };

  updateRelances = function() {
    new Url('pmsi', 'ajax_vw_relances')
      .addParam('chir_id', '{{$chirSel}}')
      .requestUpdate('relances');
  };

  initUpdateDocuments = function() {
    new Url('board', 'ajax_list_documents')
      .addParam('chir_id', '{{$chirSel}}')
      .periodicalUpdate('documents', {frequency: frequency});
  };

  updateDocuments = function() {
    new Url('board', 'ajax_list_documents')
      .addParam('chir_id', '{{$chirSel}}')
      .requestUpdate('documents');
  };

  Main.add(function () {
    Control.Tabs.create('tab-worklist', true, {afterChange: function(container) {
      if (container.id == 'actes_non_cotes') {
        updateActes();
      }
    }});
    {{if "dPprescription"|module_active}}
      showPrescriptions();
    {{/if}}
    initUpdateActes();

    {{if "messagerie"|module_active && $account->_id}}
      updateMessagerie();
    {{/if}}

    {{if "dPpmsi"|module_active}}
      initUpdateRelances();
    {{/if}}

    initUpdateDocuments();
  });
</script>

<ul id="tab-worklist" class="control_tabs me-margin-top-0 me-small">
  {{if "dPprescription"|module_active}}
    <li>
      <a href="#prescriptions">
        {{tr}}CPrescription{{/tr}}
        <small>(&ndash;)</small>
      </a>
    </li>
  {{/if}}
  <li>
    <a href="#actes_non_cotes" class="empty">
      {{tr}}Worklist.actes_non_cotes{{/tr}}
      <small>(&ndash;)</small>
    </a>
  </li>
  {{if "messagerie"|module_active}}
    <li>
      <a href="#messagerie" id="tab_messagerie">{{tr}}Worklist.messagerie{{/tr}}</a>
    </li>
  {{/if}}

  {{if "dPpmsi"|module_active}}
    <li>
      <a href="#relances">{{tr}}CRelance|pl{{/tr}} <small>(&ndash;)</small></a>
    </li>
  {{/if}}

  <li>
    <a href="#documents" class="empty">{{tr}}CCompteRendu|pl{{/tr}} <small>(&ndash;)</small></a>
  </li>
</ul>

{{if "dPprescription"|module_active}}
  <div id="prescriptions" style="display: none;" class="me-no-align"></div>
{{/if}}

<div id="actes_non_cotes" style="display: none;" class="me-no-align"></div>

{{if "messagerie"|module_active}}
  <table id="messagerie" class="tbl" style="display: none;"></table>
{{/if}}

{{if "dPpmsi"|module_active}}
  <div id="relances" style="display: none;"></div>
{{/if}}

<div id="documents" style="display: none;" class="me-no-align"></div>
