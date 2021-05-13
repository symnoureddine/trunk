{{*
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="main tbl">
  <tr>
    <td colspan="10" align="center"><h2>{{tr}}CImportConflict-handle-conflicts{{/tr}}</h2></td>
  </tr>

  <tr>
    <td class="narrow"></td>
    <th>{{tr}}CImportConflict-total-conflicts{{/tr}}</th>
    <th>{{tr}}CImportConflict-total-conflicts medecins{{/tr}}</th>
    <th colspan="2">{{tr}}Action{{/tr}}</th>
  </tr>

  <tr>
    <th>{{tr}}CMedecinImport-dry_run{{/tr}}</th>
    <td align="right">
      <span id="nb-conflicts-import-audit">{{$nb_conflicts_audit}}</span>
    </td>
    <td align="right">
      <span id="nb-conflicts-import-medecin-audit">{{$nb_medecins_audit}}</span>
    </td>
    <td class="narrow">
      <button type="button" class="search" onclick="ImportMedecins.displayAuditConflicts()">
        {{tr}}CImportConflict-display-audit-conflicts{{/tr}}
      </button>
    </td>
    <td class="narrow">
      <button type="button" class="cancel" onclick="ImportMedecins.deleteConflicts(1);">
        {{tr}}CImportConflict-import-delete-conflicts-audit{{/tr}}
      </button>
    </td>
  </tr>

  <tr>
    <th>{{tr}}CImportConflict-import{{/tr}}</th>
    <td align="right"><span id="nb-conflicts-import">{{$nb_conflicts}}</span></td>
    <td align="right"><span id="nb-conflicts-import-medecin">{{$nb_medecins}}</span></td>
    <td class="narrow">
      <button type="button" class="search" onclick="ImportMedecins.displayConflicts()">
        {{tr}}CImportConflict-display-conflicts{{/tr}}
      </button>
    </td>
    <td class="narrow">
      <button type="button" class="cancel" onclick="ImportMedecins.deleteConflicts(0);">
        {{tr}}CImportConflict-import-delete-conflicts{{/tr}}
      </button>
    </td>
  </tr>
</table>



