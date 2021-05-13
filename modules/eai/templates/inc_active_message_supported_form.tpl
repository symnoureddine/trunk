{{*
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form class="form-message-supported-{{$_family_name}}-{{$_category_name}}"
      name="editActorMessageSupported-{{$uid}}" method="post" onsubmit="return onSubmitFormAjax(this);">
  {{mb_key object=$_message_supported}}
  {{mb_class object=$_message_supported}}
  <input type="hidden" name="object_id" value="{{$_message_supported->object_id}}" />
  <input type="hidden" name="object_class" value="{{$_message_supported->object_class}}" />
  <input type="hidden" name="message" value="{{$_message_supported->message}}" />
  <input type="hidden" name="profil" value="{{$_family_name}}" />
  {{if $_message_supported->_id}}
    <input type="hidden" name="active" value="{{$_message_supported->active|ternary:'0':'1'}}" />
  {{else}}
    <input type="hidden" name="active" value="1" />
  {{/if}}

  <input type="hidden" name="callback"
         value="ExchangeDataFormat.fillMessageSupportedID.curry({{$uid}})" />

  {{if $_category_name && $_category_name != "none"}}
    <input type="hidden" name="transaction" value="{{$_category_name}}" />
  {{/if}}

  <a href="#1" onclick="this.up('form').onsubmit()"
     style="display: inline-block; vertical-align: middle;">
    {{if $_message_supported->active}}
      <i class="fa fa-toggle-on" style="color: #449944; font-size: large;"></i>
    {{else}}
      <i class="fa fa-toggle-off" style="font-size: large;"></i>
    {{/if}}
  </a>
</form>