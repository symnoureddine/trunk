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

{{assign var=element_to_tarmed value=$object}}
{{assign var=tarmed            value=$element_to_tarmed->_ref_tarmed}}

<table class="tooltip tbl">
  <tr>
    <td class="text">
      <strong>
        {{tr}}CTarmed.description{{/tr}}
      </strong>:
      {{$tarmed->libelle}}
    </td>
  </tr>
  <tr>
    <td class="text">
      <strong>{{tr}}CTarmed.interpretation{{/tr}}</strong>:
      {{$tarmed->interpretation}}
    </td>
  </tr>
</table>
