{{*
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  ObjectSelector.init = function () {
    this.sForm = 'edit-entry';
    this.sId = 'object_id';
    this.sClass = 'object_class';
    this.sView = '_object';

    this.pop();
  }
</script>

<form name="edit-entry" method="post" onsubmit="return Keeper.submitEntry(this);">
  {{mb_key object=$entry}}
  {{mb_class object=$entry}}
  <input type="hidden" name="del" value="" />
  {{mb_field object=$entry field=keychain_id hidden=true}}
  {{mb_field object=$entry field=object_class hidden=true}}
  {{mb_field object=$entry field=object_id hidden=true}}

  <table class="main form">
    {{mb_include module=system template=inc_form_table_header object=$entry colspan=4}}

    <tr>
      <th>{{mb_label object=$entry field=label}}</th>
      <td>{{mb_field object=$entry field=label}}</td>

      <th>{{mb_label object=$entry field=public}}</th>
      <td>{{mb_field object=$entry field=public}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$entry field=username}}</th>
      <td>{{mb_field object=$entry field=username}}</td>

      <th>{{mb_label object=$entry field=password}}</th>
      <td>{{mb_field object=$entry field=password canNull=true}}</td>
    </tr>

    <tr>
      <th>{{mb_label object=$entry field=object_id}}</th>

      <td colspan="3">
        <input type="text" name="_object" value="{{$entry->_ref_object}}" size="25" />

        <button class="erase notext compact" type="button"
                onclick="$V(this.form.elements.object_class, ''); $V(this.form.elements.object_id, ''); $V(this.form.elements._object, '');">
        </button>

        <button type="button" class="search notext compact" onclick="ObjectSelector.init();">
          {{tr}}common-action-Search object{{/tr}}
        </button>
      </td>
    </tr>

    <tr>
      <th>{{mb_label object=$entry field=comment}}</th>
      <td colspan="3">{{mb_field object=$entry field=comment size=50}}</td>
    </tr>

    <tr>
      <td class="button" colspan="4">
        <button type="submit" class="save">{{tr}}Save{{/tr}}</button>

        {{if $entry->_id && $entry->canEdit()}}
          <button type="button" class="trash" onclick="Keeper.confirmEntryDeletion(this.form);">
            {{tr}}common-action-Delete{{/tr}}
          </button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>

{{if $entry->_id}}
  {{mb_include module=dPsante400 template=inc_widget_list_hypertext_links object=$entry}}
{{/if}}