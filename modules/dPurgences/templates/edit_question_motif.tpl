{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="Edit-question" action="?" method="post" onsubmit="return Question.onSubmit(this);">
  {{mb_class  object=$question}}
  {{mb_key    object=$question}}
  <input type="hidden" name="motif_id" value="{{$question->motif_id}}"/>
  <table class="form">
    {{mb_include module=system template=inc_form_table_header object=$question}}
    <tr>
      <th>{{mb_label object=$question field=degre}}</th>
      <td>{{mb_field object=$question field=degre increment=true form="Edit-question"}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$question field=nom}}</th>
      <td>{{mb_field object=$question field=nom}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$question field=num_group}}</th>
      <td>{{mb_field object=$question field=num_group}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$question field=actif}}</th>
      <td>{{mb_field object=$question field=actif}}</td>
    </tr>
    <tr>
      <td class="button" colspan="2">
        {{if $question->_id}}
          <button class="submit" type="submit">{{tr}}Save{{/tr}}</button>
          <button class="trash" type="reset" onclick="return Question.confirmDeletion(this.form, this.form.nom.value);">
            {{tr}}Delete{{/tr}}
          </button>
        {{else}}
          <button class="submit" type="submit">{{tr}}Create{{/tr}}</button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>