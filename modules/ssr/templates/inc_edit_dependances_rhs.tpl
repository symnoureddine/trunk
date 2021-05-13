{{*
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=dependances value=$rhs->_ref_dependances}}
<form name="dependances-{{$rhs->_guid}}" action="?m={{$m}}" method="post" onsubmit="return onSubmitFormAjax(this);">
  {{mb_key object=$dependances}}
  {{mb_class object=$dependances}}
  <input type="hidden" name="del" value="0" />
  {{mb_field object=$dependances field=rhs_id hidden=true}}
  <table class="form">
    <tr>
      <th class="title" colspan="2">
        {{$rhs->_ref_sejour->_ref_patient}}
      </th>
    </tr>
    <tr>
      <th class="category me-text-align-right">{{tr}}Category{{/tr}}</th>
      <th class="category me-text-align-left">{{tr}}CDependancesRHS.degre{{/tr}}</th>
    </tr>
    <tr>
      <th>{{mb_label object=$dependances field=habillage}}</th>
      <td>{{mb_field object=$dependances field=habillage tabindex="10001" onchange="this.form.onsubmit()" typeEnum="radio"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dependances field=deplacement}}</th>
      <td>{{mb_field object=$dependances field=deplacement tabindex="10002" onchange="this.form.onsubmit()" typeEnum="radio"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dependances field=alimentation}}</th>
      <td>{{mb_field object=$dependances field=alimentation tabindex="10003" onchange="this.form.onsubmit()" typeEnum="radio"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dependances field=continence}}</th>
      <td>{{mb_field object=$dependances field=continence tabindex="10004" onchange="this.form.onsubmit()" typeEnum="radio"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dependances field=comportement}}</th>
      <td>{{mb_field object=$dependances field=comportement tabindex="10005" onchange="this.form.onsubmit()" typeEnum="radio"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dependances field=relation}}</th>
      <td>{{mb_field object=$dependances field=relation tabindex="10006" onchange="this.form.onsubmit()" typeEnum="radio"}}</td>
    </tr>
  </table>
</form>  