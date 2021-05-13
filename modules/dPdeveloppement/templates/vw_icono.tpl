{{*
* @package Mediboard\Developpement
* @author  SAS OpenXtrem <dev@openxtrem.com>
* @license https://www.gnu.org/licenses/gpl.html GNU General Public License
* @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=system script=gen_module_icono}}
<script>
  Main.add(
    function() {
      {{foreach from=$modules item=_module}}
        GenModuleIcono.genImage(
          'module_{{$_module->_id}}_apercu',
          'canvas_{{$_module->_id}}',
          '{{tr}}module-{{$_module->mod_name}}-trigramme{{/tr}}',
          '{{$_module->_color}}'
        );
      {{/foreach}}
    }
  );
</script>

<table class="main tbl">
  <thead>
    <tr>
      <th>
        {{tr}}CModule|pl{{/tr}}
      </th>
      <th>
        {{tr}}CModule.trigramme{{/tr}}
      </th>
      <th>
        {{mb_label class=CModule field=mod_category}}
      </th>
      <th>
        {{mb_label class=CModule field=mod_package}}
      </th>
      <th>
        {{tr}}CModule-_color{{/tr}}
      </th>
      <th>
        {{tr}}CModule.preview{{/tr}}
      </th>
      <th>
        {{tr}}CModule.effective_image{{/tr}}
      </th>
      <th>
        <button class="upload notext" id="button_module_all"
                onclick="
                  if (confirm($T('mod-dPdeveloppement-msg-update all icons-confirm'))) {
                    GenModuleIcono.uploadAll('{{$all_modules_id}}')
                  }
                  ">
        {{tr}}mod-dPdeveloppement-msg-update all icons{{/tr}}
        </button>
      </th>
    </tr>
    <tr>
      <th>
        <input type="text" onkeyup="GenModuleIcono.TableView.filter(this, 0);"/>
      </th>
      <th>
        <input type="text" onkeyup="GenModuleIcono.TableView.filter(this, 1);"/>
      </th>
      <th>
        <select name="mod_category" onchange="GenModuleIcono.TableView.filter(this, 2);">
          <option value="">&mdash; {{tr}}All{{/tr}}</option>
          {{foreach from=$category_list item=_category}}
            <option value="{{tr}}CModule.mod_category.{{$_category}}{{/tr}}">{{tr}}CModule.mod_category.{{$_category}}{{/tr}}</option>
          {{/foreach}}
        </select>
      </th>
      <th>
        <select name="mod_package" onchange="GenModuleIcono.TableView.filter(this, 3);">
          <option value="">&mdash; {{tr}}All{{/tr}}</option>
          {{foreach from=$package_list item=_package}}
            <option value="{{tr}}CModule.mod_package.{{$_package}}{{/tr}}">{{tr}}CModule.mod_package.{{$_package}}{{/tr}}</option>
          {{/foreach}}
        </select>
      </th>
      <th colspan="4"></th>
    </tr>
  </thead>
  <tbody>
  {{foreach from=$modules item=_module}}
    <tr>
      <td>
        {{tr}}module-{{$_module->mod_name}}-court{{/tr}}({{mb_value object=$_module field=mod_name}})
      </td>
      <td>
        {{tr}}module-{{$_module->mod_name}}-trigramme{{/tr}}
      </td>
      <td>
        {{mb_value object=$_module field=mod_category}}
      </td>
      <td>
        {{mb_value object=$_module field=mod_package}}
      </td>
      <td>
        <i class="fas fa-palette" style="color: {{$_module->_color}};"></i>
        {{mb_value object=$_module field=_color}}
      </td>
      <td id="module_{{$_module->_id}}_apercu">
      </td>
      <td>
        <img src="./modules/{{$_module->mod_name}}/images/iconographie/{{$app->user_prefs.LOCALE}}/icon.png"
             id="image_{{$_module->_id}}" />
      </td>
      <td>
        <button class="upload notext" id="button_module_{{$_module->_id}}"
                onclick="
                  if (confirm($T('mod-dPdeveloppement-msg-update an icon-confirm'))) {
                    GenModuleIcono.upload('{{$_module->_id}}', $('canvas_{{$_module->_id}}'), $('image_{{$_module->_id}}'), this)
                  }
                  ">
          {{tr}}mod-dPdeveloppement-msg-update an icon{{/tr}}
        </button>
      </td>
    </tr>
  {{/foreach}}
  </tbody>
</table>