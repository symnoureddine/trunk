{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<ul>
  {{foreach from=$matches key=_key item=_match}}
    <li>
      <span class="cp"><strong>{{$_match.code_postal|emphasize:$keyword}}</strong></span>
      &ndash;
      <span class="commune">{{$_match.commune|emphasize:$keyword}}</span>
      <div style="color: #888; padding-left: 1em;">
        <small>{{if $_match.departement}}{{$_match.departement}} - {{/if}}{{$_match.pays}}</small>
      </div>
      <span class="insee" style="display: none;">{{$_match.INSEE}}</span>
    </li>
  {{/foreach}}
</ul>
