{{*
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table>
  {{foreach from=$_operations_crud item=_operation_crud}}
    {{if $_operation_crud == "read"}}
      <tr>
        <td class="narrow">
          <button class="fa fa-search-plus me-primary"
                  onclick="TestFHIR.crudOperations('{{$resource}}', '{{$_operation_crud}}', $V('{{$resource}}_{{$_operation_crud}}_resource_id'))">
            {{tr}}CFHIRInteractionRead{{/tr}}
          </button>
        </td>
        <td>
          <input id="{{$resource}}_{{$_operation_crud}}_resource_id" placeholder="Resource ID" type="text" value="" />
        </td>
      </tr>
    {{/if}}
    {{if $_operation_crud == "search"}}
      <tr>
        <td colspan="2">
          <button class="fa fa-search" onclick="TestFHIR.crudOperations('{{$resource}}', '{{$_operation_crud}}');">
            {{tr}}CFHIRInteractionSearch{{/tr}}
          </button>
        </td>
      </tr>
    {{/if}}
    {{if $_operation_crud == "create"}}
      <tr>
        <td class="narrow">
          <button class="far fa-save" onclick="TestFHIR.crudOperations('{{$resource}}', '{{$_operation_crud}}', $V('{{$resource}}_{{$_operation_crud}}_resource_id'));">
            {{tr}}CFHIRInteractionCreate{{/tr}}
          </button>
        </td>
        <td>
          <input id="{{$resource}}_{{$_operation_crud}}_resource_id" placeholder="Object ID" type="number" value="" />
        </td>
      </tr>
    {{/if}}
    {{if $_operation_crud == "update"}}
      <tr>
        <td>
          <button class="far fa-edit" onclick="TestFHIR.crudOperations('{{$resource}}', '{{$_operation_crud}}', $V('{{$resource}}_{{$_operation_crud}}_resource_id'));">
            {{tr}}CFHIRInteractionUpdate{{/tr}}
          </button>
        </td>
        <td>
          <input id="{{$resource}}_{{$_operation_crud}}_resource_id" placeholder="Resource ID" type="text" value="" />
        </td>
      </tr>
    {{/if}}
    {{if $_operation_crud == "delete"}}
      <tr>
        <td>
          <button class="fa fa-eraser" onclick="TestFHIR.crudOperations('{{$resource}}', '{{$_operation_crud}}', $V('{{$resource}}_{{$_operation_crud}}_resource_id'));">
            {{tr}}CFHIRInteractionDelete{{/tr}}
          </button>
        </td>
        <td>
          <input id="{{$resource}}_{{$_operation_crud}}_resource_id" placeholder="Resource ID" type="text" value="" />
        </td>
      </tr>
    {{/if}}
    {{if $_operation_crud == "history"}}
      <tr>
        <td class="narrow">
          <button class="fa fa-history"
                  onclick="TestFHIR.crudOperations('{{$resource}}', '{{$_operation_crud}}',
                          $V('{{$resource}}_{{$_operation_crud}}_resource_id'), $V('{{$resource}}_{{$_operation_crud}}_version_id'))">
          {{tr}}CFHIRInteractionHistory{{/tr}}
          </button>
        </td>
        <td>
          <input id="{{$resource}}_{{$_operation_crud}}_resource_id" type="text" placeholder="Resource ID" value="" />
          <input id="{{$resource}}_{{$_operation_crud}}_version_id" type="number" placeholder="Version ID" value="" />
        </td>
      </tr>
    {{/if}}
    {{if $_operation_crud == "capabilities"}}
      <tr>
        <td colspan="2">
          <button class="fa fa-search-plus" onclick="TestFHIR.capabilityStatement();">
            {{tr}}CFHIRInteractionCapabilities{{/tr}}
          </button>
        </td>
      </tr>
    {{/if}}
  {{/foreach}}
</table>




