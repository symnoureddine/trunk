{{*
 * @package Mediboard\dPpatients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<div class="small-info">{{tr}}CPlageConsult-msg-Alert exercice place set{{/tr}}</div>

{{foreach from=$exercice_places item=_exercice_place}}
  <input type="radio" name="exercice_place_id" id="exercice_place_id" value="{{$_exercice_place->_id}}"
    {{if $_exercice_place->_id == $plage_consult->exercice_place_id}}checked="checked"{{/if}}/>
  <label for="{{$_exercice_place->_id}}">{{$_exercice_place->raison_sociale}} ({{$_exercice_place->adresse}}, {{$_exercice_place->cp}} {{$_exercice_place->commune}})</label>
  <br/>
{{foreachelse}}
  <div class="small-info">{{tr}}CExercicePlace-none{{/tr}}</div>
{{/foreach}}

{{if $plage_consult->exercice_place_id}}
  <input type="radio" name="exercice_place_id" id="exercice_place_id" value=""/>
  <label for="">{{tr}}CPlageConsult-msg-Exercice place delete{{/tr}}</label>
{{/if}}

