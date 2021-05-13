{{*
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    Control.Tabs.create('tabs-configure', true, {afterChange: function(container) {
      if (container.id == "CConfigEtab") {
        Configuration.edit('eai', ['CGroups'], $('CConfigEtab'));
      }
    }});
  });

  function importAsipTable() {
    new Url("eai", "ajax_import_asip_table")
      .requestUpdate("import-log");
  }
  function seeAsipDB() {
    new Url('eai', 'ajax_view_asip_db')
    .requestModal();
  }
</script>

<ul id="tabs-configure" class="control_tabs">
  <li><a href="#object-servers">{{tr}}config-object-servers{{/tr}}</a></li>
  <li><a href="#config-eai">{{tr}}config-eai{{/tr}}</a></li>
  <li><a href="#config-import-asip">{{tr}}ASIP{{/tr}}</a></li>
  <li><a href="#config-tunnel">{{tr}}Tunnel{{/tr}}</a></li>
  <li><a href="#maintenance">{{tr}}Maintenance{{/tr}}</a></li>
  <li><a href="#CConfigEtab">{{tr}}CConfigEtab{{/tr}}</a></li>
</ul>

<div id="object-servers" style="display: none;">
  {{mb_include template=inc_config_object_servers}}
</div>

<div id="config-eai" style="display: none;">
  {{mb_include template=inc_config_eai}}
</div>

<div id="config-import-asip" style="display: none;">
  {{mb_include template=inc_config_import_asip}}
</div>

<div id="config-tunnel" style="display: none;">
  {{mb_include template=inc_config_tunnel}}
</div>

<div id="maintenance" style="display: none;">
  {{mb_include template=inc_config_maintenance}}
</div>

<div id="CConfigEtab" style="display: none"></div>