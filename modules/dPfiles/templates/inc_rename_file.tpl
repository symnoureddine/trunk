{{*
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    var form = getForm("editFileName");
    var input = form.file_name;
    input.focus();
    input.caret(0, $V(input).lastIndexOf("."));
  });
</script>

<form name="editFileName" method="post" onsubmit="return onSubmitFormAjax(this, Control.Modal.close);">
  {{mb_class object=$file}}
  {{mb_key   object=$file}}

  <table class="form">
    <tr>
      <th>{{mb_label object=$file field=file_name}}</th>
      <td class="narrow">{{mb_field object=$file field=file_name size=40}}</td>
      <td>
        <button class="tick notext">{{tr}}Validate{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>
