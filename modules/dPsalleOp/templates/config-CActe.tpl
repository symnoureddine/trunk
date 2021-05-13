{{*
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="editConfig-CActe" method="post" onsubmit="return onSubmitFormAjax(this)">
  {{mb_configure module=$m}}

  <table class="form">
    {{mb_include module=system template=inc_config_enum var=check_incompatibility values=block|blockOperationAlertOthers|alert|allow}}
    {{mb_include module=system template=inc_config_bool var=envoi_actes_salle}}
    {{mb_include module=system template=inc_config_bool var=envoi_motif_depassement}}
    {{mb_include module=system template=inc_config_bool var=del_actes_non_cotes}}

    <tr>
      <td class="button" colspan="2">
        <button class="modify">{{tr}}Save{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>