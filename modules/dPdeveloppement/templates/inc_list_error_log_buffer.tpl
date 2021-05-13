{{*
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>

  bufferDelete = function (buffer_file = "all") {
    if (buffer_file === "all") {
      if (confirm("{{tr}}CErrorLog.confirm_delete_all_buffer{{/tr}}") === false) {
        return;
      }
    }

    var url = new Url("developpement", "ajax_delete_error_buffer");
    url.addParam("buffer_file", buffer_file);
    url.requestUpdate("error-logs");
  }

  bufferSave = function (buffer_file = "all") {
    if (buffer_file === "all") {
      if (confirm("{{tr}}CErrorLog.confirm_save_all_buffer{{/tr}}") === false) {
        return;
      }
    }

    var url = new Url("developpement", "ajax_save_error_buffer");
    url.addParam("buffer_file", buffer_file);
    url.requestUpdate("error-logs");
  }

</script>

<style type="text/css">
  .error-logs pre {
    width: 100%;
    border: none;
    margin: 0;
    max-height: 220px;
  }

  .error-logs tr td {
    vertical-align: top;
    padding: 1px;
  }
</style>

<table width="100%">
  <tr>
    <td>
      <div class="small-info">{{ $files|@count }} {{tr}}CErrorLog.buffer{{/tr}}</div>
    </td>
    {{ if $files|@count > 0}}
      <td style="text-align: right; width:50%">
        <button class="submit" onclick="bufferSave();">{{tr}}CErrorLog.save_all_buffer{{/tr}}</button>
        <button class="trash" onclick="bufferDelete();">{{tr}}CErrorLog.delete_all_buffer{{/tr}}</button>
      </td>
    {{/if}}
  </tr>
</table>


<table class="main tbl error-logs" id="error-logs">
  <tbody>
  <tr>
    <th class="title"></th>
    <th class="title">Name</th>
    <th class="title">Date</th>
    <th class="title">Size</th>
    <th class="title">Lines</th>
  </tr>
  {{foreach from=$files item=_file}}
    <tr>
      <td>
        <button class="submit notext" title="{{tr}}CErrorLog.save_buffer{{/tr}}" onclick="bufferSave('{{$_file.name}}');"></button>
        <button class="trash notext" title="{{tr}}CErrorLog.delete_buffer{{/tr}}" onclick="bufferDelete('{{$_file.name}}');"></button>
      </td>
      <td>{{$_file.name}}</td>
      <td>{{$_file.time|date_format:$conf.datetime}}</td>
      <td>{{$_file.size}}</td>
      <td>{{$_file.lines}}</td>
    </tr>
  {{/foreach}}
</table>