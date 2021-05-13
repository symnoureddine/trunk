{{*
 * @package Mediboard\NovxtelHospitality
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(Control.Tabs.create.curry('tabs-configure', true, {
    afterChange: function(container) {
      if (container.id == "CConfigEtab") {
        Configuration.edit('novxtelHospitality', 'CGroups', $('CConfigEtab'));
      }
    }
  }));
</script>

<ul id="tabs-configure" class="control_tabs">
  <li>
    <a href="#CConfigEtab">{{tr}}CConfigEtab{{/tr}}</a>
  </li>
  <li>
    <a href="#source_novxtel_hospitality">{{tr}}CSourceHTTP{{/tr}}</a>
  </li>
</ul>

<div id="CConfigEtab" style="display: none;"></div>

<div id="source_novxtel_hospitality" style="display: none;">
  <table class="form">
    <tr>
      <th class="title">{{tr}}config-exchange-source{{/tr}} '{{$source_novxtel_hospitality->name}}'</th>
    </tr>
    <tr>
      <td>{{mb_include module=system template=inc_config_exchange_source source=$source_novxtel_hospitality}}</td>
    </tr>
  </table>
</div>
