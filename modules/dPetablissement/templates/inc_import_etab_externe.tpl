{{*
 * @package Mediboard\Etablissement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=dPetablissement script=Group}}
<script>
  showTypes = function(type) {
    var url = new Url('dPetablissement', 'vw_types_prov_dest');
    url.addParam('type', type);
    url.requestModal();
  }
</script>

<table class="main form">
  <tr>
    <td>
      <div class="big-info">
        {{tr}}CEtabExterne-import-instructions{{/tr}}
        <ul>
          <li>{{tr}}CEtabExterne-finess-court{{/tr}}</li>
          <li>{{tr}}CEtabExterne-siret-court{{/tr}}</li>
          <li>{{tr}}CEtabExterne-ape-court{{/tr}}</li>
          <li>{{tr}}CEtabExterne-nom-court{{/tr}}</li>
          <li>{{tr}}CEtabExterne-raison_sociale-court{{/tr}}</li>
          <li>{{tr}}CEtabExterne-adresse-court{{/tr}}</li>
          <li>{{tr}}CEtabExterne-cp{{/tr}}</li>
          <li>{{tr}}CEtabExterne-ville-court{{/tr}}</li>
          <li>{{tr}}CEtabExterne-tel-court{{/tr}}</li>
          <li>{{tr}}CEtabExterne-fax-court{{/tr}}</li>
          <li>{{tr}}CEtabExterne-provenance{{/tr}}<button class="help notext" type="button" onclick="showTypes('prov')"></button></li>
          <li>{{tr}}CEtabExterne-destination{{/tr}}<button class="help notext" type="button" onclick="showTypes('dest')"></button></li>
          <li>{{tr}}CEtabExterne-priority-court{{/tr}}</li>
        </ul>
      </div>
      
      <form name="upload_form" action="?" enctype="multipart/form-data" method="post">
        <input type="hidden" name="m" value="dPetablissement" />
        <input type="hidden" name="dosql" value="do_import_etab_externe" />
        <input type="hidden" name="MAX_FILE_SIZE"  value="67108864" /><!-- 64MB -->
        
        <input type="file" name="import" class="notNull" size="60" />
        <button type="submit" class="tick">{{tr}}Import-CSV{{/tr}}</button>
        <button type="button" class="download" onclick="Group.exportEtabExterne()">{{tr}}Export{{/tr}}</button>
      </form>
    </td>
  </tr>
</table>
