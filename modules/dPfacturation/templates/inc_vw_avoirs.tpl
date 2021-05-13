{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=facturation script=facture_avoir ajax=1}}
<fieldset class="me-no-align me-no-box-shadow">
  <legend>{{tr}}CFactureAvoir|pl{{/tr}} ({{tr}}{{$object->_class}}{{/tr}})</legend>
  <table class="main tbl">
    <tr>
      <th class="category narrow">
        {{mb_label class=CFactureAvoir field=date}}
      </th>
      <th class="category">
        {{mb_label class=CFactureAvoir field=commentaire}}
      </th>
      <th class="category narrow">
        {{mb_label class=CFactureAvoir field=montant}}
      </th>
      <th class="category narrow">
        <button class="add notext" type="button"
                onclick="FactureAvoir.edit(
                  null,
                  Control.Modal.stack.length ? Control.Modal.refresh : Reglement.reload,
                  '{{$object->_class}}', '{{$object->_id}}'
                )">
          {{tr}}Add{{/tr}}
        </button>
      </th>
    </tr>
    {{assign var=total value=0}}
    {{foreach from=$object->_ref_avoirs item=_avoir}}
      <tr>
        <td>
          {{mb_value object=$_avoir field=date}}
        </td>
        <td>
          {{mb_value object=$_avoir field=commentaire}}
        </td>
        <td>
          {{mb_value object=$_avoir field=montant}}
        </td>
        <td>
          <button class="pdf notext" type="button" onclick="FactureAvoir.print('{{$_avoir->_id}}')">
            {{tr}}Print{{/tr}}
          </button>
          <button class="edit notext" type="button"
                  onclick="FactureAvoir.edit(
                    '{{$_avoir->_id}}',
                    Control.Modal.stack.length ? Control.Modal.refresh : Reglement.reload
                    )">
            {{tr}}Edit{{/tr}}
          </button>
        </td>
      </tr>
    {{foreachelse}}
      <tr>
        <td colspan="4" class="empty">{{tr}}CFactureAvoir.none{{/tr}}</td>
      </tr>
    {{/foreach}}
    {{if $object->_montant_avoir > 0}}
      <tr>
        <td colspan="2">{{tr}}Total{{/tr}}</td>
        <td>
          {{mb_value object=$object field=_montant_avoir}}
        </td>
        <td></td>
      </tr>
    {{/if}}
  </table>
</fieldset>