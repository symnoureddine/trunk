{{*
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="main tbl">
  <tr>
    <th class="title" colspan="6">
      <button type="button" class="new notext" style="float: left;" onclick="MediusersCh.editCompte(0, '{{$user->_id}}')">
        {{tr}}CMediusersCompteCh-title-create{{/tr}}
      </button>
      {{tr}}CMediusersCompteCh.all{{/tr}} {{$user->_view}}
    </th>
  </tr>
  <tr>
    <th class="narrow">{{tr}}Action{{/tr}}</th>
    <th>{{mb_title class=CMediusersCompteCh field=name}}</th>
    <th>{{mb_title class=CMediusersCompteCh field=rcc}}</th>
    <th>{{mb_title class=CMediusersCompteCh field=adherent}}</th>
    <th>{{mb_title class=CMediusersCompteCh field=debut_bvr}}</th>
    <th>{{mb_title class=CMediusersCompteCh field=banque_id}}</th>
  </tr>
  {{foreach from=$user->_ref_comptes_ch item=_compte_ch}}
    <tr>
      <td class="button">
        <button type="button" class="edit notext" onclick="MediusersCh.editCompte('{{$_compte_ch->_id}}', '{{$user->_id}}')">
          {{tr}}CMediusersCompteCh-title-modify{{/tr}}
        </button>
      </td>
      <td>{{mb_value object=$_compte_ch field=name}}</td>
      <td>{{mb_value object=$_compte_ch field=rcc}}</td>
      <td>{{mb_value object=$_compte_ch field=adherent}}</td>
      <td>{{mb_value object=$_compte_ch field=debut_bvr}}</td>
      <td>{{mb_value object=$_compte_ch field=banque_id}}</td>
    </tr>
  {{foreachelse}}
    <tr>
      <td colspan="6" class="empty">{{tr}}CMediusersCompteCh.none{{/tr}}</td>
    </tr>
  {{/foreach}}
</table>