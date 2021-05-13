{{*
 * @package Mediboard\Ftp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=ftp    script=action_sftp     ajax=true}}
{{mb_script module=system script=exchange_source ajax=true}}

{{mb_default var=light value=false}}
{{mb_default var=callback value=""}}

<table class="main">
  <tr>
    <td>
      <form name="editSourceSFTP-{{$source->name}}" action="?m={{$m}}" method="post"
            onsubmit="return onSubmitFormAjax(this, { onComplete : (function() {
              {{if $callback}}{{$callback}}{{/if}}
              
              if (this.up('.modal')) {
              Control.Modal.close();
              } else {
              ExchangeSource.refreshExchangeSource('{{$source->name}}', '{{$source->_wanted_type}}');
              }}).bind(this)})">

        <input type="hidden" name="m" value="ftp" />
        {{mb_class object=$source}}
        {{mb_key object=$source}}

        <fieldset>
          <legend>
            {{tr}}CSourceSFTP{{/tr}}
            {{mb_include module=system template=inc_object_history object=$source css_style="float: none"}}
          </legend>

          <table class="form">
            {{mb_include module=system template=CExchangeSource_inc}}

            <tr>
              <th style="width: 120px">{{mb_label object=$source field="user"}}</th>
              <td>{{mb_field object=$source field="user" size="50"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="password"}}</th>
              <td>{{mb_field object=$source field="password" size="30"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="port"}}</th>
              <td>{{mb_field object=$source field="port"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="timeout"}}</th>
              <td>{{mb_field object=$source field="timeout" register=true increment=true form="editSourceSFTP-`$source->name`" size=3 step=1 min=0}}</td>
            </tr>
          </table>
        </fieldset>


        <fieldset>
          <legend>{{tr}}CSourceFTP-manage_files{{/tr}}</legend>

          <table class="main form">
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

        {{if !$light}}
          <fieldset>
            <legend>{{tr}}utilities-source-ftp{{/tr}}</legend>

            <table class="main form">
              <tr>
                <td class="button">
                  <!-- Test connexion FTP -->
                  <button type="button" class="search" onclick="SFTP.connexion('{{$source->name}}');"
                          {{if !$source->_id}}disabled{{/if}}>
                    {{tr}}utilities-source-ftp-connexion{{/tr}}
                  </button>

                  <!-- Liste des fichiers -->
                  <button type="button" class="list" onclick="SFTP.getFiles('{{$source->name}}');"
                          {{if !$source->_id}}disabled{{/if}}>
                    {{tr}}utilities-source-ftp-getFiles{{/tr}}
                  </button>

                  <button type="button" class="lookup" onclick="ExchangeSource.manageFiles('{{$source->_guid}}');"
                          {{if !$source->_id}}disabled{{/if}}>
                    {{tr}}utilities-source-ftp-manageFiles{{/tr}}
                  </button>
                </td>
              </tr>
            </table>
          </fieldset>
        {{/if}}
      </form>
    </td>
  </tr>
</table>