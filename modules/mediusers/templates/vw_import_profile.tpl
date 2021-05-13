{{*
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=mediusers script=ImportUsers ajax=true}}

<form name="check-import-profiles" method="get" onsubmit="return onSubmitFormAjax(this, null, 'result-check-import-profiles');">
  <input type="hidden" name="m" value="mediusers"/>
  <input type="hidden" name="a" value="inc_check_import_profiles"/>

  <table class="main form">
    <tr>
      <td class="button" colspan="2" align="center">
        <h2>{{tr}}CUser-import-profile|pl{{/tr}}</h2>
      </td>
    </tr>

    <tr>
      <th><label for="directory">{{tr}}common-directory-source{{/tr}}</label></th>
      <td>
        <input type="text" size="50" name="directory" onchange="ImportUsers.checkDirectory(this);"/>
        <div id="directory-check"></div>
      </td>
    </tr>

    <tr>
      <td class="button" align="center" colspan="2">
        <button type="submit" class="import">{{tr}}CUser-check-import-profile|pl{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>

<div id="result-import-exist-profile"></div>
<div id="result-check-import-profiles"></div>