{{*
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    var url = new Url("files", "ajax_category_autocomplete");
    url.addParam("object_class", "{{$object->_class}}");
    url.addParam("yoplet", 1);
    File.applet.autocompleteCat =
      url.autoComplete(getForm('addFastFile').keywords_category, '', {
        minChars: 2,
        dropdown: true,
        width: "200px"
      });
  });
</script>

{{assign var=default_cat value='Ox\Mediboard\Files\CFilesCategory::getDefautCat'|static_call:null:$object->_class}}

<div id="modal-yoplet" style="display: none; width: 700px;">
  <form name="addFastFile" method="post" action="?"
    onsubmit="return onSubmitFormAjax(this);">
    <input type="hidden" name="m" value="files" />
    <input type="hidden" name="dosql" value="do_file_aed" />
    <input type="hidden" name="_from_yoplet" value="1" />
    <input type="hidden" name="object_class" value="" />
    <input type="hidden" name="object_id" value="" />
    <input type="hidden" name="file_date" value="now" />
    <input type="hidden" name="callback" value="File.applet.addfile_callback" />
    <input type="hidden" name="_index" value="" />
    <input type="hidden" name="_file_path" value="" />
    <input type="hidden" name="_checksum" value="" />
    
    <div style="max-height: 400px; overflow: auto;">
      <table class="tbl">
        <tr>
          <th class="title" colspan="5">
            Fichiers disponibles dans :
            <br />
             {{$app->user_prefs.directory_to_watch}}
          </th>
        </tr>
        <tr>
          <th class="narrow">
            <input type="checkbox" name="global_check" checked title="{{tr}}CFile-Check / Uncheck all{{/tr}}"
                   onclick="$$('.upload-file').invoke('writeAttribute', 'checked', this.checked)" />
          </th>
          <th>{{mb_title class=CFile field=file_name}}</th>
          <th style="width: 2em;">
            <i class="me-icon change me-primary" title="{{tr}}Send{{/tr}}"></i>
          </th>
          <th style="width: 2em;">
            <i class="me-icon merge me-primary" title="{{tr}}Link{{/tr}}"></i>
          </th>
          <th style="width: 2em;">
            <i class="me-icon trash me-primary" title="{{tr}}Delete{{/tr}}"></i>
          </th>
        </tr>
        <tbody id="file-list">
        </tbody>
      </table>
    </div>

    <hr />

    <table class="form">
      <tr>
        <th style="width: 1px;">
          {{mb_label class=CFile field=_rename}}
        </th>
        <td>
          <input type="text" name="_rename" value="" />
        </td>
      </tr>
      <tr>
        <th>
          {{mb_label class=CFile field=file_category_id}}
        </th>
        <td>
          <input type="text" name="keywords_category" value="{{if $default_cat->_id}}{{$default_cat->nom}}{{else}}&mdash; {{tr}}Choose{{/tr}}{{/if}}"
                 class="autocomplete str" autocomplete="off" onclick="this.value = '';" style="width: 12em;" />
          <input type="hidden" name="file_category_id" value="{{$default_cat->_id}}" />
        </td>
      </tr>
      {{if "dmp"|module_active}}
        <tr>
          <th>
            {{mb_label class=CFile field="type_doc_dmp"}}
          </th>
          <td>
            {{mb_field class=CFile field="type_doc_dmp" emptyLabel="Choose" style="width: 15em;"}}
          </td>
        </tr>
      {{/if}}
      <tr>
        <td colspan="2">
          <input type="checkbox" name="delete_auto" checked="checked"/>
          <label for="delete_auto">{{tr}}Delete after send{{/tr}}</label>
        </td>
      </tr>
      <tr>
        <td class="button" colspan="2">
          <button type="button" class="cancel reactive" onclick="File.applet.cancelModal();">
            {{tr}}Cancel{{/tr}}
          </button>
          <button type="button" class="change uploadinmodal" onclick="File.applet.uploadFiles();">
            {{tr}}Upload{{/tr}}
          </button>
          <button type="button" class="tick reactive" onclick="File.applet.closeModal();">
            {{tr}}Close{{/tr}}
          </button>
        </td>
      </tr>
    </table>
  </form>
</div>
