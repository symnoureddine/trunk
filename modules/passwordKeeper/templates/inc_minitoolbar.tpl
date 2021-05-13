{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=passwordKeeper script=keeper ajax=true}}

<a title="{{tr}}Ckeychain-action-Manage|pl{{/tr}}" href="#1" onclick="Keeper.manageKeychains();">
  {{me_img image_url="modules/passwordKeeper/images" icon="lock" src="icon.png" width="16" height="16"}}
</a>