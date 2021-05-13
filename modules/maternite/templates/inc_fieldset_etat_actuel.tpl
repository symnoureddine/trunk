{{*
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module="maternite" script="allaitement" ajax=1}}
{{assign var=grossesse   value=$patient->_ref_last_grossesse}}
{{assign var=allaitement value=$patient->_ref_last_allaitement}}

<div>
  <fieldset id="etat_actuel_grossesse" class="me-margin-bottom-12">
    <legend>Etat actuel</legend>

    <table class="layout">
      <tr>
        <td class="text">
          <strong>Grossesse : </strong>
          {{if $grossesse && $grossesse->_id}}
            {{$grossesse}}
          {{else}}
            &mdash;
          {{/if}}
        </td>
        <td class="narrow">
          <button type="button" class="grossesse_create notext me-tertiary" style="float: right;"
                  onclick="Grossesse.viewGrossesses('{{$patient->_id}}', null, null, 0)"></button>
        </td>
      </tr>
      <tr>
        <td class="text">
          <strong>Allaitement :</strong>
          {{if $allaitement && $allaitement->_id}}
            {{$allaitement}}
          {{else}}
            &mdash;
          {{/if}}
        </td>
        <td class="narrow">
          <button type="button" class="add notext me-tertiary" style="float: right;"
                  onclick="Allaitement.viewAllaitements('{{$patient->_id}}')"></button>
        </td>
      </tr>
    </table>
  </fieldset>
</div>