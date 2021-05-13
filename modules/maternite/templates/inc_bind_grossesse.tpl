{{*
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    Grossesse.refreshList('{{$parturiente_id}}', '{{$object_guid}}', '{{$grossesse_id}}');
  });
</script>

<button id="button_new_grossesse" class="new" onclick="Grossesse.editGrossesse(0, '{{$parturiente_id}}')"
        style="float: left;">{{tr}}CGrossesse-title-create{{/tr}}</button>

<table class="main layout">
  <tr>
    <td style="width: 40%">
      <div id="list_grossesses"></div>
      <div style="text-align: right;">
        {{if $object_guid}}
          <button id="button_select_grossesse" type="button" class="tick" onclick="Grossesse.bindGrossesse(); Control.Modal.close();">
            Sélectionner
          </button>
          <button type="button" class="cancel" onclick="Grossesse.emptyGrossesses(); Control.Modal.close();">Vider</button>
        {{else}}
          <button type="button" class="cancel" onclick="Control.Modal.close();">{{tr}}Close{{/tr}}</button>
        {{/if}}
      </div>
    </td>
    <td id="edit_grossesse"></td>
  </tr>
</table>
