{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(window.print);
</script>
<table class="print">
  <tr>
    <th class="title" colspan="4"><a href="#" onclick="window.print()">Consultation</a></th>
  </tr>
  <tr>
    <th>Date </th>
    <td style="font-size: 1.3em;">{{$consult->_ref_plageconsult->date|date_format:$conf.longdate}}</td>
    <th>Praticien</th>
    <td style="font-size: 1.3em;">Dr {{$consult->_ref_chir->_view}}</td>
  </tr>
  <tr>
    <th>Patient </th>
    <td style="font-size: 1.3em;">
      {{$patient->_view}}
      {{mb_include module=patients template=inc_vw_ipp ipp=$patient->_IPP}}
    </td>
    <td colspan="2"></td>
  </tr>
  <tr>
    <th class="category" colspan="4">Examen</th>
  </tr>
  <tr>
    <th>{{mb_label object=$consult field=motif}}</th>
    <td class="text">{{mb_value object=$consult field=motif}}</td>
    <th>{{mb_label object=$consult field=rques}}</th>
    <td class="text">{{mb_value object=$consult field=rques}}</td>
  </tr>
  <tr>
    <th>{{mb_label object=$consult field=histoire_maladie}}</th>
    <td class="text">{{mb_value object=$consult field=histoire_maladie}}</td>
    <th>{{mb_label object=$consult field=examen}}</th>
    <td class="text">{{mb_value object=$consult field=examen}}</td>
  </tr>
  {{if "dPcabinet CConsultation show_projet_soins"|gconf}}
    <tr>
      <th>{{mb_label object=$consult field=projet_soins}}</th>
      <td class="text">{{mb_value object=$consult field=projet_soins}}</td>
    </tr>
  {{/if}}
  <tr>
    <th>{{mb_label object=$consult field=conclusion}}</th>
    <td class="text">{{mb_value object=$consult field=conclusion}}</td>
    <td colspan="2"></td>
  </tr>
</table>
