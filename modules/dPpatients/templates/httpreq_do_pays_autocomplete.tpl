{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<ul>
  {{foreach from=$result item=pays}}
    <li>
      <span><strong>{{$pays.nom_fr}}</strong></span>
      <span style="display: none;"> - </span>
      <span style="display: none;">{{$pays.numerique}}</span>
    </li>
  {{/foreach}}
</ul>