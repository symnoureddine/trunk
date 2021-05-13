{{*
 * @package Mediboard\Consultations
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if "dPImeds"|module_active && $conf.ref_pays == 2}}
  <div class="button_size">
    <button type="button" class="search" onclick="refreshImeds();">{{tr}}CImeds-action-result-labo|pl{{/tr}}</button>
  </div>
{{elseif "mondialSante"|module_active}}
  <div class="button_size">
    <button type="button" class="search" onclick="MondialSante.showMessagesForPatient($V(getForm('filtreTdb').praticien_id), '{{$patient->_id}}');">
      {{tr}}CMondialSanteMessage-action-show_for_patient{{/tr}}
    </button>
  </div>
{{/if}}
