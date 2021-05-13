{{*
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    var form = getForm('search-medecin-conflict');
    form.onsubmit();
  });
</script>

<div id="list-conflicts">
  <h2>
    {{if $audit}}
      {{tr}}CImportConflict-handle-audit{{/tr}}
    {{else}}
      {{tr}}CImportConflict-handle-conflict{{/tr}}
    {{/if}}
  </h2>

  <form name="search-medecin-conflict" method="get" onsubmit="return onSubmitFormAjax(this, null, 'result-search-medecin-conflict')">
    <input type="hidden" name="m" value="openData"/>
    <input type="hidden" name="a" value="ajax_search_medecin_conflict"/>
    <input type="hidden" name="audit" value="{{$audit}}"/>

    <table class="main form">
      <tr>
        <th>{{tr}}CMedecin-nom{{/tr}}</th>
        <td><input type="text" name="nom" value=""/></td>

        <th>{{tr}}CMedecin-prenom{{/tr}}</th>
        <td><input type="text" name="prenom" value=""/></td>
      </tr>

      <tr>
        <th>{{tr}}CMedecin-cp{{/tr}}</th>
        <td><input type="text" name="cp" value=""/></td>

        <td colspan="2"></td>
      </tr>

      <tr>
        <td class="button" colspan="4">
          <button class="search" type="submit">{{tr}}Search{{/tr}}</button>
        </td>
      </tr>
    </table>
  </form>

  <div id="result-search-medecin-conflict"></div>
</div>