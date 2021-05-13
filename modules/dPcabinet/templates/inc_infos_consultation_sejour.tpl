{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if $sejour->_canRead}}
  <table class="tbl me-table-card-list">
    <tr>
      <th class="title" colspan="3">
        {{tr}}CSejour-back-consultations{{/tr}} de séjour
      </th>
    </tr>
    <tr>
      <th>{{mb_label class=CPlageconsult field="chir_id"}}</th>
      <th>{{tr}}Date{{/tr}}</th>
      <th>{{tr}}Hour{{/tr}}</th>
    </tr>
    <tbody id="consults-sejour-{{$sejour->_guid}}">
      {{foreach from=$sejour->_ref_consultations item=_consult}}
        <tr>
          <td>
            {{mb_include module=mediusers template=inc_vw_mediuser mediuser=$_consult->_ref_chir}}
          </td>
          <td>{{$_consult->_date|date_format:$conf.date}}</td>
          <td>{{mb_value object=$_consult field=heure}}</td>

        </tr>
      {{foreachelse}}
        <tr><td colspan="3" class="empty">{{tr}}CConsultation.none{{/tr}}</td></tr>
      {{/foreach}}
    </tbody>

    {{if @$modules.brancardage->_can->read && "brancardage General use_brancardage"|gconf && !"brancardage General see_round_trip_bloc"|gconf}}
      <tr>
        <td class="button" colspan="3">
          <div id="patient_pret-{{$sejour->_guid}}">
            {{mb_include module=brancardage template=inc_exist_brancard colonne="demande_brancard" object=$sejour}}
          </div>
        </td>
      </tr>
    {{/if}}
  </table>
{{elseif $sejour->_id}}
  <div class="small-info">Vous n'avez pas accès au détail des consultations.</div>
{{/if}}