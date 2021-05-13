{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=callback value=""}}

<script>
  FileSystem = {
    connexion: function (exchange_source_name) {
      var url = new Url("system", "ajax_tests_file_system");
      url.addParam("exchange_source_name", exchange_source_name);
      url.addParam("type_action", "connexion");
      url.requestModal(500, 400);
    },

    sendFile: function (exchange_source_name) {
      var url = new Url("system", "ajax_tests_file_system");
      url.addParam("exchange_source_name", exchange_source_name);
      url.addParam("type_action", "sendFile");
      url.requestModal(500, 400);
    },

    getFiles: function (exchange_source_name) {
      var url = new Url("system", "ajax_tests_file_system");
      url.addParam("exchange_source_name", exchange_source_name);
      url.addParam("type_action", "getFiles");
      url.requestModal("70%", "50%");
    },
    
    delFile: function (source_guid, path, exchange_source_name) {
      var url = new Url("system", "ajax_delete_file_system");
      url.addParam("source_guid", source_guid);
      url.addParam("path", path);
      url.requestUpdate("systemMsg", {onComplete : function () {
        Control.Modal.close();
        FileSystem.getFiles(exchange_source_name);
      }});
    }
  }
</script>

<table class="main"> 
  <tr>
    <td>
      <form name="editSourceFileSystem-{{$source->name}}" action="?m={{$m}}" method="post"
            onsubmit="return onSubmitFormAjax(this, { onComplete : (function() {
              {{if $callback}}{{$callback}}{{/if}}
              
              if (this.up('.modal')) {
                Control.Modal.close();
              } else {
                ExchangeSource.refreshExchangeSource('{{$source->name}}', '{{$source->_wanted_type}}');
              }}).bind(this)})">

        <input type="hidden" name="m" value="system" />
        <input type="hidden" name="dosql" value="do_source_file_system_aed" />
        <input type="hidden" name="source_file_system_id" value="{{$source->_id}}" />
        <input type="hidden" name="del" value="0" />

        <fieldset>
          <legend>
            {{tr}}CSourceFileSystem{{/tr}}
            {{mb_include module=system template=inc_object_history object=$source css_style="float: none"}}
          </legend>

          <table class="form">
            {{mb_include module=system template=CExchangeSource_inc}}

            <tr>
              <th>{{mb_label object=$source field="fileprefix"}}</th>
              <td>{{mb_field object=$source field="fileprefix"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="fileextension"}}</th>
              <td>{{mb_field object=$source field="fileextension"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="fileextension_write_end"}}</th>
              <td>{{mb_field object=$source field="fileextension_write_end"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="ack_prefix"}}</th>
              <td>{{mb_field object=$source field="ack_prefix"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="sort_files_by"}}</th>
              <td>{{mb_field object=$source field="sort_files_by" typeEnum="radio"}}</td>
            </tr>
          </table>
        </fieldset>

        <table class="main form">
          <tr>
            <td class="button" colspan="2">
              {{if $source->_id}}
                <button class="modify" type="submit">{{tr}}Save{{/tr}}</button>
                <button class="trash" type="button" onclick="confirmDeletion(this.form,
                  { ajax: 1, typeName: '', objName: '{{$source->_view}}'},
                  { onComplete: (function() {
                  if (this.up('.modal')) {
                    Control.Modal.close();
                  } else {
                    ExchangeSource.refreshExchangeSource('{{$source->name}}', '{{$source->_wanted_type}}');
                  }}).bind(this.form)})">

                  {{tr}}Delete{{/tr}}
                </button>
              {{else}}
                <button class="submit" type="submit">{{tr}}Create{{/tr}}</button>
              {{/if}}
            </td>
          </tr>
        </table>

        <fieldset>
          <legend>{{tr}}utilities-source-file_system{{/tr}}</legend>

          <table class="main form">
            <tr>
              <td class="button">
                <!-- Test de connexion -->
                <button type="button" class="search" onclick="FileSystem.connexion('{{$source->name}}');"
                        {{if !$source->_id}}disabled{{/if}}>
                  {{tr}}utilities-source-file_system-connexion{{/tr}}
                </button>

                <!-- Dépôt d'un fichier -->
                <button type="button" class="search" onclick="FileSystem.sendFile('{{$source->name}}');"
                        {{if !$source->_id}}disabled{{/if}}>
                  {{tr}}utilities-source-file_system-sendFile{{/tr}}
                </button>

                <!-- Liste des fichiers -->
                <button type="button" class="search" onclick="FileSystem.getFiles('{{$source->name}}');"
                        {{if !$source->_id}}disabled{{/if}}>
                  {{tr}}utilities-source-file_system-getFiles{{/tr}}
                </button>
              </td>
            </tr>
          </table>
        </fieldset>
      </form>
    </td>
  </tr>
</table>