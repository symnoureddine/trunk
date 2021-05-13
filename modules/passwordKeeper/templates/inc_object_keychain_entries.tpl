{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if 'passwordKeeper'|module_active && $modules.passwordKeeper->_can->read}}
  {{mb_script module=passwordKeeper script=keeper ajax=true}}

  <a href="#1" style="float: right;" onclick="Keeper.setContext('{{$object->_guid}}');"
     onmouseover="ObjectTooltip.createEx(this,'{{$object->_guid}}', 'keychain_entries')">
    <img src="modules/passwordKeeper/images/icon.png" width="16" height="16" />
  </a>
{{/if}}