{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $validation_xml->_logs_erreur|@count}}
  <div class="small-warning">{{tr}}tarmed-send_file-check_elements{{/tr}}:</div>
  <table class="main tbl" style="text-align: center;">
    {{mb_include module=facturation template=vw_facture_elt_miss_items}}
  </table>
{{/if}}

{{if $facture->msg_error_xml}}
  <div class="small-error">
    <strong>{{tr}}CFacture-msg_error_xml-warning{{/tr}}</strong>
    {{mb_value object=$facture field=msg_error_xml}}
  </div>
{{/if}}

{{if !$validation_xml->_logs_erreur|@count && !$facture->msg_error_xml}}
  <div class="small-info">
    {{tr}}CEditBill-info_force_send{{/tr}}
  </div>
{{/if}}