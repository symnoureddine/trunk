{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="main tbl">
  <tr>
    <th>{{mb_label object=$journal field=date_envoi}}</th>
    <td>{{mb_value object=$journal field=date_envoi}}</td>
  </tr>
  <tr>
    <th>{{mb_label object=$journal field=user_id}}</th>
    <td>
      <span onmouseover="ObjectTooltip.createEx(this, 'CMediusers-{{$journal->user_id}}}');">
        {{mb_value object=$journal field=user_id}}
      </span>
    </td>
  </tr>
  <tr>
    <th>{{mb_label object=$journal field=error}}</th>
    <td>{{mb_value object=$journal field=error}}</td>
  </tr>
  <tr>
    <th colspan="2">{{mb_label object=$journal field=statut}}</th>
  </tr>
  <tr>
    <td colspan="2">
      {{if $statut|is_array}}
        <table class="main tbl" style="text-align: center;">
          {{mb_include module=facturation template=vw_facture_elt_miss_items validation_xml=$statut_xml}}
        </table>
      {{else}}
        {{$statut|nl2br}}
      {{/if}}
    </td>
  </tr>
</table>