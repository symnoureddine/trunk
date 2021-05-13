{{*
 * @package Mediboard\Xds
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="editConfig" method="post" onsubmit="return onSubmitFormAjax(this);">
  {{mb_configure module=$m}}

  <table class="form">
    <tr>
      <th class="title" colspan="2">Configuration</th>
    </tr>

    <tr>
      <td class="button" colspan="2">
        <button class="submit">{{tr}}Save{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>