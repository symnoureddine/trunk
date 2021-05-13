{{*
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=system script=exchange_source}}

<script type="text/javascript">
  Exchange = {
    purge: function (force, source_class) {
      var form = getForm('EchangePurge' + source_class);

      if (!force && !$V(form.auto)) {
        return;
      }

      if (!checkForm(form)) {
        return;
      }

      new Url('system', 'ajax_purge_echange')
        .addFormData(form)
        .requestUpdate("purge-echange-" + source_class);
    }
  };

  Source = {
    tab: null,

    edit: function (source_guid) {
      new Url("eai", "ajax_edit_source")
        .addParam("source_guid", source_guid)
        .requestModal(600, null, {onClose: Source.refresh.curry(source_guid)});
    },

    refreshAll: function (source_class) {
      new Url("eai", "ajax_refresh_exchange_sources")
        .addParam("source_class", source_class)
        .requestUpdate(source_class + "_sources", function () {
          if (Source.tab) {
            ExchangeSource.SourceAvailability(Source.tab.activeContainer);
          }
        });
    },

    refresh: function (source_guid) {
      new Url("eai", "ajax_refresh_exchange_source")
        .addParam("source_guid", source_guid)
        .requestUpdate("line_" + source_guid, ExchangeSource.SourceAvailability.curry(Source.tab.activeContainer));
    },

    showTrace : function (source_guid) {
      new Url("eai", "ajax_show_trace")
        .addParam("source_guid", source_guid)
        .requestModal('80%', '80%');
    },
    
    createSource : function () {
      new Url("eai", "ajax_select_source_type")
        .requestModal('80%', '100%');
    }
  };

  Main.add(function () {
    Source.tab = Control.Tabs.create("tabs-sources", true, {
      afterChange: function (container) {
        Source.refreshAll(container.id);
      }
    });

    ExchangeSource.SourceAvailability.curry(Source.tab.activeContainer);
  });
</script>

<button type="button" class="add" onclick="Source.createSource();">{{tr}}CExchangeSource-msg-New source{{/tr}}</button>

<ul id="tabs-sources" class="control_tabs small me-margin-top-6">
{{foreach from=$all_sources key=name item=_sources}}
  <li>
    <a href="#{{$name}}" {{if $count_exchange.$name == 0}}class="empty"{{/if}}>
      {{tr}}{{$name}}{{/tr}} ({{$count_exchange.$name}})
    </a>
  </li>
{{/foreach}}
</ul>

{{foreach from=$all_sources key=name item=_sources}}
  <div id="{{$name}}" style="display: none;">
    <table class="tbl me-striped">
      <tr>
        <th class="section button" colspan="2"></th>
        <th class="section">
          {{tr}}CExchangeSource-role-court{{/tr}}
        </th>
        <th class="section" title="{{tr}}CExchangeSource-active{{/tr}}">
          {{tr}}CExchangeSource-active-court{{/tr}}
        </th>
        <th class="section" title="{{tr}}CExchangeSource-loggable{{/tr}}">
          {{tr}}CExchangeSource-loggable-court{{/tr}}
        </th>
        <th class="section" style="width: 30%">
          {{tr}}CExchangeSource-name{{/tr}}
        </th>
        <th class="section"></th>
        <th class="section" colspan="2">
          {{tr}}Time-response{{/tr}} / {{tr}}Message{{/tr}}
        </th>
      </tr>
      <tbody id="{{$name}}_sources">
      </tbody>
    </table>
    {{if $count_exchange.$name > 0 && $name === "CSourceSOAP" || $name === "CSourceFTP"}}
    <br/>
    <table class="form">
      <tr>
        <th class="title">{{tr}}Purge{{/tr}}</th>
      </tr>
      <tr>
        <td>
          {{mb_include module=system template=inc_purge_echange source_class=$name}}
        </td>
      </tr>
    </table>
    {{/if}}
  </div>
{{/foreach}}
