{{*
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=mediusers script=export_mediusers}}

<h2>Actions de maintenances</h2>

<table class="tbl">
  <tr>
    <th class="narrow">{{tr}}Action{{/tr}}</th>
    <th>{{tr}}Status{{/tr}}</th>
  </tr>

  <tr>
    <td>
      <button class="fas fa-external-link-alt" onclick="ExportMediusers.openExportMediusersXml();">
        {{tr}}CMediusers-export-xml{{/tr}}
      </button>
    </td>
  </tr>

  <tr>
    <td>
      <button class="import" onclick="ExportMediusers.openImportMediusers();">
        {{tr}}CMediusers-import-xml{{/tr}}
      </button>
    </td>
  </tr>

  <tr>
    <td>
      <button class="import" onclick="ExportMediusers.openImportProfile();">
        {{tr}}CUser-import-profile|pl{{/tr}}
      </button>
    </td>
  </tr>
  
	<tr>
    <td>
      <button class="hslip" onclick="ExportMediusers.addPerms();">
        Mise à jour des droits des utilisateurs
      </button>
    </td>
    <td id="resultDroits">
    </td>
  </tr>
</table>
