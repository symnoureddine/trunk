{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=show_button value=true}}
{{mb_default var=readonly    value=false}}
{{mb_default var=inline      value=false}}
{{mb_default var=float      value='right'}}
{{mb_default var=form_name   value="edit-`$object->_guid`"}}
{{mb_default var=callback    value="MbObject.edit"}}

{{if $show_button}}
  <button style="float: right;" class="tag-edit me-tertiary" type="button" onclick="Tag.manage('{{$object->_class}}')">
    Gérer les tags
  </button>
{{/if}}

<ul class="tags" {{if $inline}} style="display: inline-block; float: {{$float}};"{{/if}}>
  {{foreach from=$object->_ref_tag_items item=_item name=tag_items}}
    <li data-tag_item_id="{{$_item->_id}}" style="background-color: #{{$_item->_ref_tag->color}}" class="tag">
      {{$_item}}

      {{if !$readonly}}
        <button type="button" class="delete"
                onclick="Tag.removeItem($(this).up('li').getAttribute('data-tag_item_id'), {{$callback}}.curry('{{$object->_guid}}'))">
        </button>
      {{/if}}
    </li>
  {{/foreach}}

  {{if !$readonly}}
    <li class="input">
      <input type="text" name="_bind_tag_view" class="autocomplete" size="15" />

      <script>
        Main.add(function () {
          var form = getForm("{{$form_name}}");
          var element = form.elements._bind_tag_view;
          var url = new Url("system", "ajax_seek_autocomplete");

          url.addParam("object_class", "CTag");
          url.addParam("input_field", element.name);
          url.addParam("where[object_class]", "{{$object->_class}}");
          url.autoComplete(element, null, {
            minChars:           2,
            method:             "get",
            select:             "view",
            dropdown:           true,
            afterUpdateElement: function (field, selected) {
              var id = selected.getAttribute("id").split("-")[2];
              Tag.bindTag("{{$object->_guid}}", id, {{$callback}}.curry("{{$object->_guid}}"));

              if ($V(element) == "") {
                $V(element, selected.down('.view').innerHTML);
              }
            }
          });
        });
      </script>
    </li>
  {{/if}}
</ul>