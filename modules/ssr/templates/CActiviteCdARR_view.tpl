{{*
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if !$object->_can->read}}
  <div class="small-info">
    {{tr}}{{$object->_class}}{{/tr}} : {{tr}}access-forbidden{{/tr}}
  </div>
  {{mb_return}}
{{/if}}

{{mb_include module=system template=CMbObject_view}}

{{assign var=activite value=$object}}

<table class="tooltip tbl">
  <tr>
    <td>
      <strong>{{mb_label object=$activite field=type}}</strong>:
      {{$activite->_ref_type_activite}}
    </td>
  </tr>
</table>
