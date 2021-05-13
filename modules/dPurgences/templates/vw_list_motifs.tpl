{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=readonly value=0}}

<script>
  Main.add(function () {
    PairEffect.initGroup("serviceEffect");
    {{if $motifs|@count == 1}}
      {{assign var=only_chap value=$chapitres|@reset}}
      var chapitre = $('{{$only_chap->_guid}}-trigger');
      if (chapitre.hasClassName('triggerShow')) {
        chapitre.click();
      }
    {{/if}}
  });
</script>
<table style="width: 100%;" class="tbl">
  <tr {{if $readonly}}style="display: none;" {{/if}}>
    <td colspan="4">
      <button type="button" class="new" onclick="Motif.edit(0)">
        {{tr}}CMotif-title-create{{/tr}}
      </button>
    </td>
  </tr>
  <tr>
    <th class="category">{{mb_title class=CMotif field=code_diag}}</th>
    <th class="category">{{mb_title class=CMotif field=nom}}</th>
    <th class="category">Degrés</th>
    {{if $readonly}}
      <th class="category narrow"></th>
    {{/if}}
  </tr>
  {{foreach from=$chapitres item=chapitre}}
    <tr id="{{$chapitre->_guid}}-trigger">
      <td colspan="4">{{$chapitre->nom}}</td>
    </tr>
    <tbody class="serviceEffect" id="{{$chapitre->_guid}}">
      {{foreach from=$chapitre->_ref_motifs item=motif}}
        <tr class="{{if $chapitre->_id == $chapitre_id && !$readonly}}selected{{/if}} {{if !$motif->actif}}hatching{{/if}}"  >
          <td style="text-align: center">
            {{if !$readonly}}<a href="#{{$motif->_guid}}" onclick="Motif.edit('{{$motif->_id}}');">{{/if}}
              {{mb_value object=$motif field=code_diag}}
            {{if !$readonly}} </a>{{/if}}
          </td>

          <td>
            {{if !$motif->actif}}<span style="color:red;float: right;">(INACTIF) </span>{{/if}}
            {{if $readonly}}<a href="#{{$motif->_guid}}" onclick="Motif.edit('{{$motif->_id}}', 1);">{{/if}}
              {{mb_value object=$motif field=nom}}
            {{if $readonly}} </a>{{/if}}
          </td>
          <td>
            <strong>
              {{if $motif->degre_min <= 1 && $motif->degre_max >=1}}[1]{{/if}}
              {{if $motif->degre_min <= 2 && $motif->degre_max >=2}}[2]{{/if}}
              {{if $motif->degre_min <= 3 && $motif->degre_max >=3}}[3]{{/if}}
              {{if $motif->degre_min <= 4 && $motif->degre_max >=4}}[4]{{/if}}
            </strong>
          </td>
          {{if $readonly}}
            <td>
              <button type="button" class="tick notext"
                      onclick="Motif.selectDiag('{{$motif->code_diag}}', '{{$motif->_id}}');"></button>
            </td>
          {{/if}}
        </tr>
      {{foreachelse}}
        <tr>
          <td class="empty" colspan="4">
            {{tr}}CMotif.none{{/tr}}
          </td>
        </tr>
      {{/foreach}}
    </tbody>
  {{foreachelse}}
    <tr>
      <td class="empty" colspan="4">
        {{tr}}CChapitreMotif.none{{/tr}}
      </td>
    </tr>
  {{/foreach}}
</table>