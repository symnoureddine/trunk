{{*
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=date value=""}}

{{mb_script module=astreintes script=plage ajax=true}}
<a href="#" class="singleclick" onclick="PlageAstreinte.modaleastreinteForDay();" title="{{tr}}CPlageAstreinte{{/tr}}">
  {{me_img src="phone.png" icon="phone-alt" alt_tr="CPlageAstreinte" icon="phone"}}
</a>