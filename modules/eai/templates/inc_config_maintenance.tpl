{{*
 * @package Mediboard\ihe
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=eai script=interop_actor}}

<button type="button" class="change" onclick="InteropActor.addProfilSupportedMessage();">
  {{tr}}CInteropActor-msg-Add profil supported message{{/tr}}
</button>

<div id="add_profil_supported_messages"></div>