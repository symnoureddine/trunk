{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="tbl">
  <tr>
    <th class="narrow"></th>
    <th>
      {{mb_title class=Ox\Mediboard\Patients\CSourceIdentite field=mode_obtention}}
    </th>
    <th>
      {{mb_title class=Ox\Mediboard\Patients\CSourceIdentite field=type_justificatif}}
    </th>
    <th>
      {{mb_title class=Ox\Mediboard\Patients\CSourceIdentite field=nom_naissance}}
    </th>
    <th>
      {{mb_title class=Ox\Mediboard\Patients\CSourceIdentite field=prenom_naissance}}
    </th>
    <th>
      {{mb_title class=Ox\Mediboard\Patients\CSourceIdentite field=prenoms}}
    </th>
    <th>
      {{mb_title class=Ox\Mediboard\Patients\CSourceIdentite field=date_naissance}}
    </th>
    <th>
      {{mb_title class=Ox\Mediboard\Patients\CSourceIdentite field=sexe}}
    </th>
    <th>
      {{mb_title class=Ox\Mediboard\Patients\CSourceIdentite field=pays_naissance_insee}}
    </th>
    <th>
      {{mb_title class=Ox\Mediboard\Patients\CSourceIdentite field=commune_naissance_insee}}
    </th>
    <th class="narrow"></th>
    <th class="narrow"></th>
  </tr>

  {{foreach from=$patient->_ref_sources_identite item=_source_identite}}
    <tr>
      <td>
        {{if $_source_identite->_id == $patient->source_identite_id}}
          <i class="fas fa-check" title="{{tr}}CSourceIdentite-selected{{/tr}}"></i>
        {{/if}}
      </td>
      <td>
        {{mb_value object=$_source_identite field=mode_obtention}}
      </td>
      <td>
        {{mb_value object=$_source_identite field=type_justificatif}}

        {{if $_source_identite->_ref_justificatif && $_source_identite->_ref_justificatif->_id}}
          <div>
            {{thumbnail document=$_source_identite->_ref_justificatif profile=small style="width:50px"}}
          </div>
        {{/if}}
      </td>
      <td>
        {{mb_value object=$_source_identite field=nom_naissance}}
      </td>
      <td>
        {{mb_value object=$_source_identite field=prenom_naissance}}
      </td>
      <td>
        {{mb_value object=$_source_identite field=prenoms}}
      </td>
      <td>
        {{mb_value object=$_source_identite field=date_naissance}}
      </td>
      <td>
        {{mb_value object=$_source_identite field=sexe}}
      </td>
      <td>
        {{mb_value object=$_source_identite field=_pays_naissance_insee}}
      </td>
      <td>
        {{mb_value object=$_source_identite field=_lieu_naissance}}
      </td>
      <td>
        {{if $_source_identite->mode_obtention === 'insi'}}
          <div class="me-margin-bottom-8">
            {{mb_include module=ameli template=services/inc_insiidir_button}}
          </div>

          <button type="button" class="search" onclick="SourceIdentite.showINS('{{$_source_identite->_id}}');">
            {{tr}}CSourceIdentite-Display ins{{/tr}}
          </button>
        {{/if}}
      </td>
      <td>
        {{mb_include module=system template=inc_object_history object=$_source_identite}}
      </td>
    </tr>
  {{foreachelse}}
    <tr>
      <td colspan="11" class="empty">
        {{tr}}CSourceIdentite.none{{/tr}}
      </td>
    </tr>
  {{/foreach}}
</table>
