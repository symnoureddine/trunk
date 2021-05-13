{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    initPaysField('addJustificatif', '_source__pays_naissance_insee');
    InseeFields.initCPVille('addJustificatif', '_source_cp_naissance', '_source_lieu_naissance', '_source_commune_naissance_insee', '_source__pays_naissance_insee');
  });
</script>

<form name="addJustificatif" method="post" onsubmit="return Patient.submitJustificatif();">
  <table class="form">
    <tr>
      <td colspan="2">
        {{mb_include module=system template=inc_inline_upload lite=true multi=false paste=false}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CSourceIdentite field=type_justificatif}}
      </th>
      <td>
        <select name="_type_justificatif">
          <option value="passeport">{{tr}}CSourceIdentite.type_justificatif.passeport{{/tr}}</option>
          <option value="carte_identite">{{tr}}CSourceIdentite.type_justificatif.carte_identite{{/tr}}</option>
          <option value="acte_naissance">{{tr}}CSourceIdentite.type_justificatif.acte_naissance{{/tr}}</option>
          <option value="livret_famille">{{tr}}CSourceIdentite.type_justificatif.livret_famille{{/tr}}</option>
          <option value="carte_sejour">{{tr}}CSourceIdentite.type_justificatif.carte_sejour{{/tr}}</option>
          <option value="doc_asile">{{tr}}CSourceIdentite.type_justificatif.doc_asile{{/tr}}</option>
          <option value="carte_identite_electronique">{{tr}}CSourceIdentite.type_justificatif.carte_identite_electronique{{/tr}}</option>
        </select>
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source__date_fin_validite}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source__date_fin_validite form=addJustificatif register=true extra="class='map_field'"}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_civilite}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_civilite}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_nom}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_nom}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_prenom}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_prenom canNull=false onchange="Patient.copyPrenom(this, true);"}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_prenoms}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_prenoms onchange="Patient.copyPrenom(this, true);"}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_prenom_usuel}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_prenom_usuel canNull=true}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_nom_jeune_fille}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_nom_jeune_fille canNull=false}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_sexe}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_sexe canNull=false}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_naissance}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_naissance canNull=false}}
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_cp_naissance}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_cp_naissance}}
        <div style="display: none;" class="autocomplete" id="_source_cp_naissance_insee_auto_complete"></div>
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source_lieu_naissance}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source_lieu_naissance}}
        {{mb_field class=CPatient field=_source_commune_naissance_insee hidden=true}}
        <div style="display: none;" class="autocomplete" id="_source_lieu_naissance_insee_auto_complete"></div>
      </td>
    </tr>
    <tr>
      <th>
        {{mb_label class=CPatient field=_source__pays_naissance_insee}}
      </th>
      <td>
        {{mb_field class=CPatient field=_source__pays_naissance_insee}}
        <div style="display: none;" class="autocomplete" id="_source__pays_naissance_insee_auto_complete"></div>
      </td>
    </tr>

    <tr>
      <td class="button" colspan="2">
        {{if $patient_id}}
          <button class="save me-primary">{{tr}}Save{{/tr}}</button>
        {{else}}
          <button class="import me-primary">{{tr}}CIdInterpreter.report_data{{/tr}}</button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>
