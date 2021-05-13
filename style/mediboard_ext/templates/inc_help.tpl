{{*
 * @package Mediboard\Style\Mediboard
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=show value=false}}
{{mb_default var=show_img value=true}}
{{mb_default var=root value=false}}

{{if "didacticiel"|module_active}}
  {{mb_script module="didacticiel" script="PermanentDidacticiel" ajax=true}}
  <a href="#1" title="{{tr}}portal-help{{/tr}}" onclick="PermanentDidacticiel.checkTutorials()" class="userMenu-help me-color-black-medium-emphasis">
    {{if $show_img}}
      <img src="style/mediboard_ext/images/icons/help.png"/>
    {{/if}}
    {{if $show}}
      {{tr}}portal-help{{/tr}}
    {{/if}}
  </a>

{{elseif "support"|module_active}}
  <a href="#1" title="{{tr}}portal-help{{/tr}}" onclick="Support.showHelp()" class="userMenu-help me-color-black-medium-emphasis">
    {{if $show_img}}
      <img src="style/{{$uistyle}}/images/icons/help.png" alt="{{tr}}portal-help{{/tr}}" />
    {{/if}}
    {{if $show}}
      {{tr}}portal-help{{/tr}}
    {{/if}}
  </a>
{{/if}}
