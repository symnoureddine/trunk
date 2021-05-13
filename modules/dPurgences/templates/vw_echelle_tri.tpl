{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=echelle_tri value=$rpu->_ref_echelle_tri}}
{{mb_ternary var=readonly_echelle_tri  test=$rpu->echelle_tri_valide value=true other=false}}
<script>
  Main.add(function () {
    {{if $rpu->_id}}
      var form = getForm('formEchelleTri');
      form.antidiabetique.hidden = 'hidden';
      form.anticoagulant.hidden = 'hidden';
      if (form.antidiabet_use.value == 'oui') {
        form.antidiabetique.hidden = '';
      }
      if (form.anticoagul_use.value == 'oui') {
        form.anticoagulant.hidden = '';
      }
      Motif.setPupilles('pupille_gauche', 0);
      Motif.setPupilles('pupille_droite', 0);
    {{/if}}
    Motif.readonly_echelle_tri = '{{$readonly_echelle_tri}}';
  });
</script>

<fieldset>
  <legend>Compléments</legend>
  {{if $rpu->_id}}
    <form name="formEchelleTri" action="#" method="post" onsubmit="return Motif.deleteDiag(this, 1);">
      {{mb_class  object=$echelle_tri}}
      {{mb_key    object=$echelle_tri}}
      <input type="hidden" name="rpu_id" value="{{$rpu->_id}}" />
      <input type="hidden" name="pupille_gauche"    value="{{$echelle_tri->pupille_gauche|default:0}}" />
      <input type="hidden" name="pupille_droite"    value="{{$echelle_tri->pupille_droite|default:0}}" />
      <input type="hidden" name="reactivite_droite" value="{{$echelle_tri->reactivite_droite}}" />
      <input type="hidden" name="reactivite_gauche" value="{{$echelle_tri->reactivite_gauche}}" />
      <table class="form me-no-box-shadow me-no-align">
        <tr>
          <td colspan="3" class="button">
            <button type="button" class="forms" onclick="Modal.open($('choose_glasgow'));">Déterminer le score glasgow</button>
          </td>
        </tr>
        <tr>
          <th><span onmouseover="ObjectTooltip.createDOM(this,'pupilles_tooltip');">Pupilles</span></th>
          <td>
            <div style="float: left;margin-right: 5px;font-size: 1.4em;text-align: center;cursor: default;">
              <div style="height:12px;{{if $echelle_tri->reactivite_gauche == 'reactif'}}font-weight: bold;color:red;{{/if}}margin-top: -3px;"
                   onclick="Motif.setReactivite('reactivite_gauche', 'reactif');" id="reactivite_gauche_reactif">+</div>
              <div style="height:12px;{{if $echelle_tri->reactivite_gauche == 'non_reactif'}}font-weight: bold;color:red;{{/if}}"
                   onclick="Motif.setReactivite('reactivite_gauche', 'non_reactif');" id="reactivite_gauche_non_reactif">-</div>
            </div>

            <div style="border: 1px solid black;display: inline-block;width:20px;height:20px;border-radius: 50%;float: left;margin-right: 5px;" onclick="Motif.setPupilles('pupille_gauche', 1);">
              <div style="display: inline-block;width:0px;height:0px;border-radius: 50%;border: 2px solid black;margin:8px;" id="pupille_gauche_circle"></div>
            </div>

            <div style="border: 1px solid black;display: inline-block;width:20px;height:20px;border-radius: 50%;float: left;" onclick="Motif.setPupilles('pupille_droite', 1);">
              <div style="border: 2px solid black;display: inline-block; width:0px;height:0px;border-radius: 50%;margin:8px;" id="pupille_droite_circle"></div>
            </div>

            <div style="float: left;margin-left: 6px;font-size: 1.4em;text-align: center;cursor: default;">
              <div  style="height:12px;{{if $echelle_tri->reactivite_droite == 'reactif'}}font-weight: bold;color:red;{{/if}}margin-top: -3px;"
                    onclick="Motif.setReactivite('reactivite_droite', 'reactif');" id="reactivite_droite_reactif">+</div>
              <div  style="height:12px;{{if $echelle_tri->reactivite_droite == 'non_reactif'}}font-weight: bold;color:red;{{/if}}"
                    onclick="Motif.setReactivite('reactivite_droite', 'non_reactif');" id="reactivite_droite_non_reactif">-</div>
            </div>
          </td>
          <td></td>
        </tr>

        {{if (!"maternite"|module_active || !@$modules.maternite->_can->read) && ($patient && $patient->sexe == "f" && ($patient->_annees >= 10 && $patient->_annees <= 55))}}
          <tr>
            <th style="width: 33%;">{{mb_label object=$echelle_tri field=enceinte}}</th>
            <td colspan="2">
              {{mb_field object=$echelle_tri field=enceinte onchange="Motif.seeSA(this.form, 1);" readonly=$readonly_echelle_tri typeEnum="radio"}}
            </td>
          </tr>
          <tr id="see_semaine_grossesse" {{if !$echelle_tri->enceinte}}style="display: none;" {{/if}}>
            <th style="width: 33%;">{{mb_label object=$echelle_tri field=semaine_grossesse}}</th>
            <td colspan="2">
              {{mb_field object=$echelle_tri field=semaine_grossesse increment=1 min=1 form="formEchelleTri"
                onchange="Motif.deleteDiag(this.form, 1);"  readonly=$readonly_echelle_tri}}
            </td>
          </tr>
        {{/if}}

        {{if (!$patient || $patient->sexe != "m") && (("maternite"|module_active && @$modules.maternite->_can->read && $sejour->_ref_grossesse->_id) || $echelle_tri->enceinte)}}
          <tr>
            <th>
            <span onmouseover="ObjectTooltip.createDOM(this,'proteinurie_tooltip');">
              {{mb_label object=$echelle_tri field=proteinurie}}
            </span>
            </th>
            <td colspan="2">
              {{mb_field object=$echelle_tri field=proteinurie emptyLabel="Choose" onchange="this.form.onsubmit();"
                readonly=$readonly_echelle_tri}}
            </td>
          </tr>
          <tr>
            <th>
            <span onmouseover="ObjectTooltip.createDOM(this,'liquide_amniotique_tooltip');">
              {{mb_label object=$echelle_tri field=liquide}}
            </span>
            </th>
            <td colspan="2">
              {{mb_field object=$echelle_tri field=liquide emptyLabel="Choose" onchange="this.form.onsubmit();"
                readonly=$readonly_echelle_tri}}
            </td>
          </tr>
        {{/if}}

        <tr>
          <th colspan="3" class="category ">Traitements</th>
        </tr>
        <tr>
          <th style="width: 33%;">{{mb_label object=$echelle_tri field=antidiabetique}}</th>
          <td style="width: 33%;">
            {{mb_field object=$echelle_tri field=antidiabet_use onchange="Motif.seeTraitements(this.form);"
              readonly=$readonly_echelle_tri}}
          </td>
          <td>
            {{mb_field object=$echelle_tri field=antidiabetique emptyLabel="Choose" onchange="Motif.seeTraitements(this.form);"
              readonly=$readonly_echelle_tri}}
          </td>
        </tr>
        <tr>
          <th>{{mb_label object=$echelle_tri field=anticoagulant}}</th>
          <td>
            {{mb_field object=$echelle_tri field=anticoagul_use onchange="Motif.seeTraitements(this.form);"
              readonly=$readonly_echelle_tri}}
          </td>
          <td>
            {{mb_field object=$echelle_tri field=anticoagulant emptyLabel="Choose" onchange="Motif.seeTraitements(this.form);"
              readonly=$readonly_echelle_tri}}
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <hr />
            {{mb_label object=$echelle_tri field=signe_clinique}}:
            {{if $readonly_echelle_tri}}
              {{mb_field object=$echelle_tri field=signe_clinique readonly=true}}
            {{else}}
              {{mb_field object=$echelle_tri field=signe_clinique onchange="Motif.seeTraitements(this.form);"}}
            {{/if}}
          </td>
        </tr>
      </table>

      <table class="form" style="display: none;" id="choose_glasgow">
        <tr>
          <th class="title" colspan="2">Glasgow</th>
        </tr>
        <tr>
          <th>{{mb_label object=$echelle_tri field=ouverture_yeux}}</th>
          <td>{{mb_field object=$echelle_tri field=ouverture_yeux emptyLabel="Choose" readonly=$readonly_echelle_tri}}</td>
        </tr>
        <tr>
          <th>{{mb_label object=$echelle_tri field=rep_verbale}}</th>
          <td>{{mb_field object=$echelle_tri field=rep_verbale emptyLabel="Choose" readonly=$readonly_echelle_tri}}</td>
        </tr>
        <tr>
          <th>{{mb_label object=$echelle_tri field=rep_motrice}}</th>
          <td>{{mb_field object=$echelle_tri field=rep_motrice emptyLabel="Choose" readonly=$readonly_echelle_tri}}</td>
        </tr>
        <tr>
          <td class="button" colspan="2">
            {{if !$readonly_echelle_tri}}
              <button type="button" class="save"
                      onclick="Motif.saveGlasgow(this.form, '{{$rpu->_ref_sejour->_guid}}');Control.Modal.close();">
                {{tr}}Save{{/tr}}
              </button>
            {{/if}}
            <button type="button" class="cancel" onclick="Control.Modal.close();">{{tr}}Close{{/tr}}</button>
          </td>
        </tr>
      </table>
    </form>
  {{/if}}
</fieldset>