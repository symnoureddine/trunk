{{*
 * @package Mediboard\MbImport
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    Control.Tabs.create('mb-import-tabs', true);
  });

  function importEntity(form) {
    new Url('mbImport', 'ajax_import')
      .addParam('mapper_name', $V(form.mapper_name))
      .addParam('last_id', $V(form.last_id))
      .addParam('auto', ($V(form.auto)) ? '1' : '0')
      .requestUpdate($V(form.mapper_name) + '-result');

    return false;
  }

  function nextImport(mapper_name, last_id, auto) {
    var form = getForm('import-' + mapper_name);

    $V(form.last_id, last_id);

    form.auto.checked = (auto === '1') ? 'checked' : null;

    if (form.auto.checked) {
      setTimeout(function() { form.onsubmit(); }, 1000);
    }
  }
</script>

<table class="main laytout">
  <col style="width: 10%;" />

  <tr>
    <td>
      <ul id="mb-import-tabs" class="control_tabs_vertical">
        {{foreach from=$mappers key=_name item=_mapper}}
          <li><a href="#{{$_name}}-tab">{{$_name}}</a></li>
        {{/foreach}}
      </ul>
    </td>

    <td>
      {{foreach from=$mappers key=_name item=_mapper}}
        <div id="{{$_name}}-tab" style="display: none;">
          <form name="import-{{$_name}}" method="get" onsubmit="return importEntity(this);">
            <input type="hidden" name="mapper_name" value="{{$_name}}" />

            <label>
              À partir de l'ID :

              <input type="text" name="last_id" value="" />
            </label>

            <label>
              Auto

              <input type="checkbox" name="auto" />
            </label>

            <button type="submit" class="fas fa-file-import fa-lg">
              Import
            </button>
          </form>

          <div id="{{$_name}}-result"></div>
        </div>
      {{/foreach}}
    </td>
  </tr>
</table>