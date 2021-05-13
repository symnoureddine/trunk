{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<tr>
  <th class="category" colspan="4">
    Dossier médical
    {{if $sejour->_NDA}}
     - [{{mb_value object=$sejour field="_NDA"}}]
    {{/if}}
  </th>
</tr>
  
<tr>
  <th style="width: 20%;">{{mb_label object=$consult field="_date"}}</th>
  <td>
    {{mb_value object=$consult field="_date"}} 
    {{mb_value object=$consult field="heure"}}
  </td>
  
  <th style="width: 25%">{{mb_label object=$consult field="motif"}}</th>
  <td>{{mb_value object=$consult field="motif"}}</td>
</tr>
  
<tr>
  
  <th>{{mb_label object=$patient field="tel"}}</th>
  <td>{{mb_value object=$patient field="tel"}}</td>
  
  <th>{{mb_label object=$patient field="adresse"}}</th>
  <td>{{mb_value object=$patient field="adresse"}}</td>
</tr>

<tr>
  <th>{{mb_label object=$patient field="tel2"}}</th>
  <td>{{mb_value object=$patient field="tel2"}}</td>
  
  <th>{{mb_label object=$patient field="cp"}} - {{mb_label object=$patient field="ville"}}</th>
  <td>{{mb_value object=$patient field="cp"}} {{mb_value object=$patient field="ville"}}</td>
</tr>

<tr>
  <th>{{mb_label object=$patient field="medecin_traitant"}}</th>
  <td colspan="3">{{mb_value object=$patient field="medecin_traitant"}}</td>
</tr>