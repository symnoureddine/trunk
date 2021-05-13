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

{{mb_script module=ssr script=csarr ajax=1}}

{{mb_include module=system template=CMbObject_view}}

{{assign var=acte       value=$object}}

{{if $acte->type == 'presta_ssr'}}
  {{assign var=presta   value=$acte->_ref_presta_ssr}}

  <table class="tooltip tbl">
    <tr>
      <td class="text">
        <strong>
          {{tr}}CPrestaSSR-libelle-court{{/tr}}
        </strong>:
        {{$presta->libelle}}
      </td>
    </tr>
    <tr>
      <td class="text">
        <strong>
          {{tr}}CPrestaSSR-type-court{{/tr}}
        </strong>:
        {{$presta->type}}
      </td>
    </tr>
    <tr>
      <td class="text">
        <strong>
          {{tr}}CPrestaSSR-description-court{{/tr}}
        </strong>:
        {{$presta->description|smarty:nodefaults}}
      </td>
    </tr>
  </table>
{{/if}}

{{if $acte->type == 'tarmed'}}
  {{assign var=tarmed value=$acte->_ref_tarmed}}

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
{{/if}}
