{{*
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}


{{assign var=cp value=$match->_refs_commune_cp|@first}}

<span class="view">{{$match->commune}}
{{if $cp && $cp->_id}}
   <small style="float : right">{{$cp->code_postal}}</small>
{{else}}
  {{tr}}No result{{/tr}}
{{/if}}
</span>