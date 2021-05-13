{{*
 * @package Mediboard\Style\Mediboard
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $applicationVersion.releaseCode}}
  <span class="release-info me-release-info">
    <label title="{{$applicationVersion.title}}" class="branch-name">
      {{$applicationVersion.releaseTitle}}
    </label>
  </span>
{{elseif $applicationVersion.title}}
  <span class="release-info me-release-info">
    {{tr}}Latest update{{/tr}}
    <label title="{{$applicationVersion.title}}"
           {{if in_array($applicationVersion.relative.unit, array("second", "minute", "hour"))}}style="font-weight: bold"{{/if}}>
     {{$applicationVersion.relative.locale}}
    </label>
  </span>
{{/if}}