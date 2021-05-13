{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    var form = getForm('show_keychain_entries');
    form.onsubmit();
  });
</script>

<form name="show_keychain_entries" method="get" onsubmit="return onSubmitFormAjax(this, null, 'keychain_entries');">
  <input type="hidden" name="m" value="passwordKeeper" />
  <input type="hidden" name="a" value="ajax_show_entries" />
  <input type="hidden" name="keychain_id" value="{{$keychain->_id}}" />
</form>

<div id="keychain_entries"></div>