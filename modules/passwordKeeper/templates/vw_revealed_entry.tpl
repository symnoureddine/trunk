{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $revealed}}
  <script>
    prompt($T('common-msg-Your password:'), {{$revealed|json|smarty:nodefaults}});
  </script>
{{/if}}
