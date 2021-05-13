{{*
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if !$object->_can->read}}
  <div class="small-info">
    {{tr}}{{$object->_class}}{{/tr}} : {{tr}}access-forbidden{{/tr}}
  </div>
  {{mb_return}}
{{/if}}

{{mb_script module=compteRendu script=document ajax=1}}
{{mb_script module=files script=document_item ajax=true}}

{{assign var=document value=$object}}

<script>
  trashDoc = function(form, file_view) {
    return confirmDeletion(form, {typeName: "le document", objName: file_view}, function() {
      if (window.loadAllDocs) {
        loadAllDocs();
      }
    });
  };

  archiveDoc = function(form) {
    if (confirm($T("CFile-comfirm_cancel"))) {
      $V(form.annule, 1);
      return onSubmitFormAjax(form, function() {
        if (window.loadAllDocs) {
          loadAllDocs();
        }
      });
    }
  };

  restoreDoc = function(form) {
    $V(form.annule, 0);
    return onSubmitFormAjax(form, function() {
      if (window.loadAllDocs) {
        loadAllDocs();
      }
    });
  };

  toggleDeliveries = function(element, guid) {
    $(guid + '-deliveries').toggle();
    element.toggleClassName('fa-caret-square-right');
    element.toggleClassName('fa-caret-square-down');
  };
</script>
<table class="tbl">
  <tr>
    <th class="title text">
      {{mb_include module=files template="inc_file_synchro" docItem=$document}}
      {{mb_include module=system template=inc_object_idsante400}}
      {{mb_include module=system template=inc_object_history}}
      {{mb_include module=system template=inc_object_notes}}
      {{$object}}
    </th>
  </tr>
</table>

<table class="main">
  <tr>
    {{assign var=file value=$document->_ref_file}}
    {{if $document->object_id && $app->user_prefs.pdf_and_thumbs && $file->_id}}
    <td id="thumbnail-{{$document->_id}}" style="text-align: center; width: 66px;">
     <a href="#1" onclick="new Url().ViewFilePopup('{{$document->object_class}}', '{{$document->object_id}}', '{{$document->_class}}', '{{$document->_id}}')">
       {{thumbnail document=$document profile=medium style="max-width: 64px; max-height: 92px; border: 1px solid black;"}}
     </a>
    </td>

    {{else}}
    <td style="text-align: center; width: 66px;">
      <img src="images/pictures/medifile.png" />
    </td>
    {{/if}}

    <td style="vertical-align: top;" class="text">
      {{foreach from=$object->_specs key=prop item=spec}}
        {{mb_include module=system template=inc_field_view}}
      {{/foreach}}
      <strong>{{mb_label class=CFile field=_file_size}}</strong> : {{mb_value object=$file field=_file_size}} <br />
      <strong>{{tr}}CCompteRendu.count_words{{/tr}}</strong> : {{$document->_source|count_words}}
      {{if $document->_count_deliveries > 0}}
        <br><strong>{{tr}}CCompteRendu.count_sending{{/tr}}</strong> : {{$document->_count_deliveries}}
        <span class="far fa-lg fa-caret-square-right" onclick="toggleDeliveries(this, '{{$document->_guid}}');" style="cursor: pointer;"></span>
        <ul id="{{$document->_guid}}-deliveries" style="display: none;">
          {{foreach from=$document->_ref_deliveries item=delivery}}
            {{foreach from=$delivery->_receivers item=receiver}}
              <li>{{$receiver}}{{if $delivery->_delivery_medium != 'mail'}}&nbsp;<strong>({{$delivery->_delivery_medium|ucfirst}})</strong>{{/if}}</li>
            {{/foreach}}
          {{/foreach}}
        </ul>
      {{/if}}
    </td>
  </tr>

  <tr>
    <td class="button" colspan="2">
      {{if $document->_can->edit}}
        {{if !$document->object_id}}
          <a class="button search" href="?m=compteRendu&tab=vw_modeles&compte_rendu_id={{$document->_id}}">
            {{tr}}Open{{/tr}}
          </a>
        {{else}}
          <button type="button" class="edit" onclick="Document.edit('{{$document->_id}}')">{{tr}}Edit{{/tr}}</button>
          <button type="button" class="print" onclick="
          {{if $app->user_prefs.pdf_and_thumbs}}
            Document.printPDF('{{$document->_id}}', {{if $document->signature_mandatory}}1{{else}}0{{/if}}, {{if $document->valide}}1{{else}}0{{/if}});
          {{else}}
            Document.print('{{$document->_id}}');
          {{/if}}">{{tr}}Print{{/tr}}</button>

          <form name="actionDoc{{$document->_guid}}" method="post">
            <input type="hidden" name="m" value="compteRendu" />
            <input type="hidden" name="dosql" value="do_modele_aed" />
            {{mb_key object=$document}}
            {{mb_field object=$document field=annule hidden=1}}

            {{if $document->annule}}
              <button type="button" class="undo" onclick="restoreDoc(this.form)">{{tr}}Restore{{/tr}}</button>
            {{else}}
              <button type="button" class="cancel" onclick="archiveDoc(this.form)">{{tr}}Cancel{{/tr}}</button>
            {{/if}}
            <button type="button" class="trash" onclick="trashDoc(this.form, '{{$document->_view|JSAttribute|smarty:nodefaults}}')">{{tr}}Delete{{/tr}}</button>
          </form>
        {{/if}}

        <button type="button" class="fa fa-share-alt" onclick="DocumentItem.viewRecipientsForSharing('{{$document->_guid}}')">{{tr}}Send{{/tr}}</button>

        {{if "dmp"|module_active}}
          {{mb_include module=dmp template=inc_buttons_files_dmp _doc_item=$document}}
        {{/if}}
      {{/if}}
    </td>
  </tr>
</table>
