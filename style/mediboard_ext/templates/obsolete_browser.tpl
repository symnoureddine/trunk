{{*
 * @package Mediboard\Style\Mediboard
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $ua->isObsolete()}}
  <div class="obsolete-browser-warning">
    <img src="images/icons/error.png" />
    Il semble que vous utilisez le navigateur {{$ua->browser_name}} {{$ua->browser_version}}, qui n'est plus pris en charge.
    <a href="#1" onclick="Modal.open('obsolete-browser-info', {width: 500, showClose: true, title: 'Navigateur incompatible'})" style="font-weight: bold;">En savoir plus</a>
  </div>

  <div id="obsolete-browser-info" style="display: none; padding: 1.5em; font-size: 1.5em;">
    <p>
      Vous devez mettre à jour votre navigateur, ou installer un navigateur alternatif,
      comme Mozilla Firefox ou Google Chrome, qui sont pris en charge.
    </p>

    <p>
      Vous pouvez contacter votre service informatique afin de procéder à ces manipulations.
    </p>

    <p>
      Voici la liste des versions minimales :
    </p>
    <ul>
      {{foreach from='Ox\Mediboard\System\CUserAgent'|static:supported_browsers item=_version key=_name}}
        <li>{{$_name}} {{$_version}}</li>
      {{/foreach}}
    </ul>
  </div>
{{/if}}