{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=facturation script=rejet ajax=true}}

<script>
  Main.add(function() {
    var tabs = Control.Tabs.create('tabs-configure', true);
    if (tabs.activeLink.key == "rejets_xml_all") {
      Rejet.searchFactureRejet(getForm("choice_rejet_all"));
    }
  });
</script>

<ul id="tabs-configure" class="control_tabs">
  <li><a href="#rejets_xml_chir">{{tr}}CFactureRejet-to_traite{{/tr}}</a></li>
  <li><a href="#rejets_xml_all">{{tr}}CFactureRejet-saved{{/tr}}</a></li>
</ul>

<div id="rejets_xml_chir" style="display: none;">
  <form name="choice-prat_rejet" action="" method="get">
    <table class="form">
      <tr>
        <th>{{tr}}CFacture-praticien_id{{/tr}}</th>
        <td>
          <select name="chir_id" style="width: 15em;">
            <option value="0" {{if !$chir_id}} selected="selected" {{/if}}>&mdash; {{tr}}CMediusers-select-professionnel{{/tr}}</option>
            {{mb_include module=mediusers template=inc_options_mediuser selected=$chir_id list=$listChir}}
          </select>
        </td>
      </tr>
      <tr>
        <td class="button" colspan="6">
          <button type="button" onclick="Rejet.refreshList(this.form);" class="submit" >{{tr}}Validate{{/tr}}</button>
        </td>
      </tr>
    </table>
  </form>
  <div id="list_rejets_xml_chir">
    {{mb_include module=facturation template=vw_list_file_rejet}}
  </div>
</div>

<div id="rejets_xml_all" style="display: none;">
  <form name="choice_rejet_all" action="" method="get">
    <table class="form">
      <tr>
        <th>{{mb_label object=$rejet field=praticien_id}}</th>
        <td>
          <select name="praticien_id" style="width: 15em;">
            <option value="0" {{if !$rejet->praticien_id}} selected="selected" {{/if}}>&mdash; {{tr}}CMediusers-select-professionnel{{/tr}}</option>
            {{mb_include module=mediusers template=inc_options_mediuser selected=$rejet->praticien_id list=$listChir}}
          </select>
        </td>
        <th>{{mb_label object=$rejet field=file_name}}</th>
        <td>{{mb_field object=$rejet field=file_name}}</td>
        <th>{{mb_label object=$rejet field=name_assurance}}</th>
        <td>{{mb_field object=$rejet field=name_assurance}}</td>
      </tr>
      <tr>
        <th>{{mb_label object=$rejet field=num_facture}}</th>
        <td>{{mb_field object=$rejet field=num_facture}}</td>
        <th>{{mb_label object=$rejet field=date}}</th>
        <td>{{mb_field object=$rejet field=date form="choice_rejet_all" register=true}}</td>
        <th>{{mb_label object=$rejet field=statut}}</th>
        <td>{{mb_field object=$rejet field=statut emptyLabel="Choose"}}</td>
      </tr>
      <tr>
        <td class="button" colspan="9">
          <button type="button" onclick="Rejet.searchFactureRejet(this.form);" class="submit" >{{tr}}Validate{{/tr}}</button>
        </td>
      </tr>
    </table>
  </form>
  <div id="list_rejets_facture"></div>
</div>