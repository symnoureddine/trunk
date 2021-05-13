{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table style="width: 100%;" class="tbl">
  <tr>
    <td>
      <button type="button" class="new" onclick="Chapitre.edit(0)">
        {{tr}}CChapitreMotif-title-create{{/tr}}
      </button>
    </td>
  </tr>
  <tr>
    <th class="category">{{mb_title class=CChapitreMotif field=nom}}</th>
  </tr>
  {{foreach from=$chapitres item=chapitre}}
    <tr>
      <td>
        <a href="#{{$chapitre->_guid}}" onclick="Chapitre.edit('{{$chapitre->_id}}');">
          {{$chapitre->nom}}
        </a>
      </td>
    </tr>
  {{foreachelse}}
    <tr>
      <td class="empty" colspan="3">
        {{tr}}CChapitreMotif.none{{/tr}}
      </td>
    </tr>
  {{/foreach}}
</table>