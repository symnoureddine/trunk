{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="searchMotif" action="#" method="get" onsubmit="return Motif.searchMotif();" class="prepared">
  <table class="form">
    <tr>
      <th colspan="2" class="title">Recherche de motif</th>
    </tr>
    <tr>
      <th>{{tr}}Search{{/tr}}</th>
      <td><input type="text" name="search" value="{{$search}}"/></td>
    </tr>

    <tr>
      <th>{{tr}}CChapitreMotif-nom{{/tr}}</th>
      <td>
        <select name="chapitre_id">
          <option value="">&mdash; {{tr}}Choose{{/tr}}</option>
          {{foreach from=$chapitres item=chapitre}}
            <option value="{{$chapitre->_id}}" {{if $chapitre_id == $chapitre->_id || $chapitre_id == $chapitre->_id}}selected="selected"{{/if}}>
              {{$chapitre->_view}}
            </option>
          {{/foreach}}
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" class="button">
        <button type="button" class="search" onclick="Motif.searchMotif();">{{tr}}Search{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>

<div id="reload_search_motif">
  {{mb_include module=urgences template=vw_list_motifs chapitres=$chapitres_search readonly=true}}
</div>

<form name="choiceMotifRPU" action="#" method="post">
  {{mb_class object=$rpu}}
  {{mb_key   object=$rpu}}
  <input type="hidden" name="del" value="0" />
  <input type="hidden" name="code_diag" value="" />
</form>