{{*
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    Control.Tabs.create('tabs-tests_resources', true);
  });
</script>

{{mb_include module="eai" template="inc_form_session_receiver"}}

{{* Format de la request *}}
<form name="request_options_fhir">
  <table class="form">
    <tr>
      <th><label for="response_type" title="Format de la réponse">Format de la réponse</label></th>
      <td>
        <label for="response_type">fhir+json</label>
        <input type="radio" name="response_type" value="json" />
        <label for="response_type_xml">fhir+xml</label>
        <input type="radio" name="response_type" value="xml" checked/>
      </td>
    </tr>
  </table>
</form>

<table class="form">
  <tr>
    <td style="vertical-align: top; width: 100px">
      <ul id="tabs-tests_resources" class="control_tabs_vertical">
        {{foreach from=$resources->_categories key=resource item=_operations_crud}}
          <li><a href="#CFHIRResource{{$resource}}">{{tr}}CFHIRResource{{$resource}}{{/tr}}</a></li>
        {{/foreach}}
      </ul>
    </td>

    <td style="vertical-align: top;">
      {{foreach from=$resources->_categories key=resource item=_operations_crud}}
        <div id="CFHIRResource{{$resource}}" style="display: none">
          {{mb_include template="inc_vw_crud_operation"}}
        </div>
      {{/foreach}}
    </td>
  </tr>
</table>

<div id="result_crud_operations"> </div>
