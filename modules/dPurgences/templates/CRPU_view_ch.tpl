{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_include template=CMbObject_view}}

<script>
  printDossier = function(id) {
    new Url("dPurgences", "print_dossier")
      .addParam("rpu_id", id)
      .popup(700, 550, "RPU");
  }
</script>

<table class="tbl tooltip">
  <tr>
    <td class="button">
      <button type="button" class="print" onclick="printDossier({{$object->_id}})">
        {{tr}}Print{{/tr}} dossier
      </button>
    </td>
  </tr>
</table>