{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=passwordKeeper script=keeper}}
{{mb_script module=system         script=object_selector}}

<script>
  Main.add(function () {
    {{if $challenge && $keychain_id}}
      Keeper.checkChallenge('{{$keychain_id}}');
    {{else}}
      Keeper.showKeychains('all-keychains');
    {{/if}}
  });
</script>

<div id="all-keychains"></div>