{{*
* @package Mediboard\Developpement
* @author  SAS OpenXtrem <dev@openxtrem.com>
* @license https://www.gnu.org/licenses/gpl.html GNU General Public License
* @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<style>
  #route-match pre {
    max-height: 100%;
  }
</style>
<div id="route-match">
    <div class="small-info">{{$url}}</div>
    {{if $error }}
      <div class="small-error">{{$error}}</div>
    {{else}}

    {{$json|highlight:json|smarty:nodefaults}}
  {{/if}}
</div>