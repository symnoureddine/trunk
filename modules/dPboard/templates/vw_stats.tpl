{{*
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="ChoixStat" method="post" action="#">
  <label for="stat" title="Statistiques à afficher">Statistiques</label>
  <select name="stat" onchange="this.form.submit()">
  {{foreach from=$stats item=_stat}}
    <option value="{{$_stat}}" {{if $_stat == $stat}}selected="selected"{{/if}}> 
      {{tr}}mod-dPboard-tab-{{$_stat}}{{/tr}}
    </option>
  {{/foreach}}
  </select>
</form>

{{if !$stat}}
<div class="big-info">
  Plusieurs statistiques sont disponibles pour le praticien.
  <br />Merci d'en <strong>sélectionner</strong> une dans la liste ci-dessus.
</div>
{{/if}}
