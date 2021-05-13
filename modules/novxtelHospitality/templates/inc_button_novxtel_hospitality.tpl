{{*
 * @package Mediboard\NovxtelHospitality
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if !"novxtelHospitality General show_button_iframe"|gconf}}
  {{mb_return}}
{{/if}}

{{mb_script module=novxtelHospitality script=novxtelHospitality ajax=1}}
{{mb_default var=notext value=notext}}

<button type='button' class="novxtel {{$notext}}"
        onclick="NovxtelHospitality.openNovxtelHospitality('{{$_sejour->_id}}')"
        title="{{tr}}CSourceNovxtelHospitality-msg-Contextual call to the HOSPITALITY software{{/tr}}">
  <img src="modules/novxtelHospitality/images/icon.png" style="width: 16px; height: 16px;" /> Novxtel - Hospitality
</button>
