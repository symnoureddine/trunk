{{*
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=dry_run value=1}}
{{mb_default var=last_id value=0}}
{{mb_default var=continue value=0}}
{{mb_default var=no_update value=0}}
{{mb_default var=complete_tel value=0}}
{{mb_default var=file_size value=0}}
{{mb_default var=cp_mandaroty value=0}}

{{if $file_size}}
  <script>
    Main.add(function () {
      ImportMedecins.total_size = {{$file_size}};
    });
  </script>
{{/if}}

<form name="medecin-get-up-to-date-file" method="get" onsubmit="return onSubmitFormAjax(this, {onComplete: function() {
    var url = new Url('openData', 'ajax_vw_import_medecins');
    url.requestUpdate('vw-import-medecins');
  }}, 'result-get-file')">
  <input type="hidden" name="m" value="openData" />
  <input type="hidden" name="a" value="ajax_get_medecin_file" />

  <table class="main tbl">
    <tr>
      <td class="narrow">
        <button class="tick" type="submit">{{tr}}CMedecinImport-csv-file-get-last{{/tr}}</button>
        <a href="https://esante.gouv.fr/securite/annuaire-sante/rpps-adeli" target="_blank" class="button info"
           title="{{tr}}CMedecinImport-Infos on file source{{/tr}}">
          {{tr}}CMedecinImport-Infos{{/tr}}
        </a>
      </td>
      <td id="result-get-file"></td>
    </tr>
    {{if $last_modification}}
      <tr>
        <td>
          {{tr}}CMedecinImport-file-version{{/tr}} {{$actual_version|date_format:$conf.date}}
          <br />

          {{if $file_error}}
            <span class="warning">{{$file_error}}</span>
            <br />
          {{else}}
            <span class="{{if $version == $actual_version}}ok{{else}}error{{/if}}">
                {{tr}}CMedecinImport-file-last-version{{/tr}} : {{$version|date_format:$conf.date}}
              </span>
            <br />
          {{/if}}

          <span class="{{if $delta_file_update > 30}}warning{{else}}ok{{/if}}">
          {{tr}}CMedecinImport-file-last-update{{/tr}} {{$last_modification|date_format:$conf.datetime}}
        </span>
        </td>
      </tr>
    {{/if}}


  </table>
</form>

<form name="medecin-do-import" method="get" onsubmit="return onSubmitFormAjax(this, null, 'import-logs')">
  <input type="hidden" name="m" value="openData" />
  <input type="hidden" name="a" value="ajax_import_medecins" />
  <input type="hidden" name="suppressHeaders" value="1" />
  <input type="hidden" name="nosleep" value="1" />
  <input type="hidden" name="version" value="{{$actual_version}}" />
  <input type="hidden" name="continue" value="1" />

  <h2>Import des médecins</h2>
  <table class="main form">
    <tr>
      <th class="narrow"><label for="count">{{tr}}CMedecinImport-count{{/tr}}</label></th>
      <td>
        <select name="count">
          {{foreach from=$counts item=_count}}
            <option value="{{$_count}}">{{$_count}}</option>
          {{/foreach}}
        </select>
      </td>
    </tr>

    <tr>
      <th class="narrow"><label for="last_id">{{tr}}CMedecinImport-last_id{{/tr}}</label></th>
      <td><input type="number" name="last_id" readonly value="{{$last_id}}" />
        / {{if $file_size}}{{$file_size|number_format:0:',':' '}}{{else}}0{{/if}} octets
      </td>
    </tr>

    <tr>
      <th></th>
      <td>
        {{assign var=total_pct value=0}}
        {{if $file_size && $file_size > 0}}
          {{math assign=total_pct equation="(x/y)*100" x=$last_id y=$file_size}}
        {{/if}}

        <progress id="progress_import_medecins" value="{{$last_id}}" max="{{$file_size}}"></progress>
        <span id="pct-import-rpps">{{$total_pct|number_format:2:',':' '}}</span>%
      </td>
    </tr>

    <tr>
      <th class="narrow"><label for="dry_run">{{tr}}CMedecinImport-dry_run{{/tr}}</label></th>
      <td><input type="checkbox" name="dry_run" value="1" {{if $dry_run}}checked{{/if}}/></td>
    </tr>

    <tr>
      <th class="narrow"><label for="no_update">{{tr}}CMedecinImport-no-update{{/tr}}</label></th>
      <td><input type="checkbox" name="no_update" value="1" {{if $no_update}}checked{{/if}}/></td>
    </tr>

    <tr>
      <th class="narrow"><label for="default_cp" title="{{tr}}CMedecinImport-default cp-desc{{/tr}}">
          {{tr}}CMedecinImport-default cp{{/tr}}
        </label></th>
      <td><input type="text" name="default_cp" {{if $default_cp}}value="{{$default_cp}}"{{/if}}/></td>
    </tr>

    <tr>
      <th class="narrow"><label for="cp_mandatory" title="{{tr}}CMedecinImport-Cp mandatory-desc{{/tr}}">
          {{tr}}CMedecinImport-Cp mandatory{{/tr}}
        </label></th>
      <td><input type="checkbox" name="cp_mandatory" value="1" {{if $default_cp}}checked{{/if}}/></td>
    </tr>

    <tr>
      <th class="narrow" title="{{tr}}CmedecinImport-type-desc{{/tr}}">{{tr}}CMedecinImport-import-rpps-or-adeli{{/tr}}</th>
      <td>
        <label><input type="radio" name="type" value="all" checked /> {{tr}}CMedecinImport.type.all{{/tr}}</label>
        <label><input type="radio" name="type" value="rpps" /> {{tr}}CMedecinImport.type.rpps{{/tr}}</label>
        <label><input type="radio" name="type" value="adeli" /> {{tr}}CMedecinImport.type.adeli{{/tr}}</label>
      </td>
    </tr>

    <tr>
      <td class="button" colspan="2">
        <button id="start-import-medecin" type="button" class="change" onclick="ImportMedecins.startImport();">
          {{tr}}Import{{/tr}}
        </button>

        <button id="stop-import-medecin" type="button" class="far fa-stop-circle" onclick="ImportMedecins.stopImport();" disabled>
          {{tr}}Pause{{/tr}}
        </button>

        <button type="button" class="erase" onclick="ImportMedecins.resetImport();">{{tr}}Reset{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>

<h2>{{tr}}CMedecinImport-stats{{/tr}}</h2>

<div id="import-complete-logs"></div>

<script>
  ImportMedecins.updateStats();
</script>

<br />

<div id="import-logs"></div>
