{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  updatePraticienCount = function () {
    var list = $V($("praticien_ids"));
    $('praticien-count').update(list.length);

    var formSejour = getForm("export-sejours-form");
    $V(formSejour["praticien_id[]"], list);

    var formPatients = getForm("export-patients-form");
    $V(formPatients["praticien_id[]"], list);

    $V($("praticien_ids_view"), list.join(","));
  };

  Main.add(function () {
    updatePraticienCount();
    Control.Tabs.create("export-tabs", true);
  })
</script>

<table class="main layout">
  <tr>
    <td class="narrow" style="vertical-align: bottom;">
      <h2>{{tr}}CGroups{{/tr}} : {{$group}}</h2>
      Praticiens de l'établissement (<span id="praticien-count">0</span> sélectionnés)
    </td>
    <td style="width: 500px;">
      <ul id="export-tabs" class="control_tabs">
        {{if "dPplanningOp"|module_active}}
          <li><a href="#export-sejours">Séjours</a></li>
        {{/if}}
        <li><a href="#export-patients">Patients</a></li>
      </ul>
    </td>
  </tr>

  <tr>
    <td rowspan="2">
      <select id="praticien_ids" multiple size="40" onclick="updatePraticienCount()">
        {{foreach from=$praticiens item=_prat}}
          <option value="{{$_prat->_id}}" {{if in_array($_prat->_id,$praticien_id)}}selected{{/if}}
                  onmouseover="ObjectTooltip.createEx(this, '{{$_prat->_guid}}')">
            #{{$_prat->_id|pad:5:0}} - {{$_prat}}
          </option>
        {{/foreach}}
      </select>
      <input type="text" id="praticien_ids_view" size="30" onfocus="this.select()" />
      <button class="up notext" onclick="$V('praticien_ids', $V('praticien_ids_view').split(/,/))"></button>
    </td>

    <td>
      <div id="export-sejours" style="display: none;">
        {{mb_include module=dPpatients template=inc_archive_sejours}}
      </div>

      <div id="export-patients" style="display: none;">
        {{mb_include module=dPpatients template=inc_export_patients}}
      </div>
    </td>
  </tr>
</table>