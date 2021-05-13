{{*
 * @package Mediboard\BloodSalvage
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Calendar.regField(getForm("selectPatient").date, null, {noView: true});
</script>

<form action="?" name="selectPatient" method="get">
  <input type="hidden" name="m" value="{{$m}}" />
  <input type="hidden" name="tab" value="vw_bloodSalvage_sspi" />

  <table class="form">
    <tr>
      <th class="category" colspan="2">
        {{$date|date_format:$conf.longdate}}
        <input type="hidden" name="date" class="date" value="{{$date}}" onchange="this.form.submit()" />
      </th>
    </tr>
  </table>
</form>

<table class="tbl">
  <tr>
    <th>Salle</th>
    <th>Praticien</th>
    <th>Patient</th>
    <th>Entr�e r�veil</th>
    <th>Sortie r�veil</th>
  </tr>

  {{foreach from=$listReveil item=rspo}}
    <tr class="hoverable">
      <td class="text">
        <a href="?m=bloodSalvage&tab=vw_bloodSalvage_sspi&op={{$rspo->_id}}" title="G�rer le Cell Saver">
          {{$rspo->_ref_salle}}
        </a>
      </td>
      <td class="text">
        <a href="?m=bloodSalvage&tab=vw_bloodSalvage_sspi&op={{$rspo->_id}}" title="G�rer le Cell Saver">
          Dr {{$rspo->_ref_chir}}
        </a>
      </td>
      <td class="text">
        <a href="?m=bloodSalvage&tab=vw_bloodSalvage_sspi&op={{$rspo->_id}}" title="G�rer le Cell Saver">
          {{$rspo->_ref_sejour->_ref_patient}}
        </a>
      </td>
      <td class="text">{{mb_value object=$rspo field=entree_reveil}}</td>
      <td class="text">{{mb_value object=$rspo field=sortie_reveil_reel}}</td>
    </tr>
    {{foreachelse}}
    <tr>
      <td colspan="10" class="empty">{{tr}}CBloodSalvage.none{{/tr}}</td>
    </tr>
  {{/foreach}}
</table>
