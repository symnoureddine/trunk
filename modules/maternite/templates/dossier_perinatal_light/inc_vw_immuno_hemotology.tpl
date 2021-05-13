{{*
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_default var=css value=""}}

<table class="main" style="{{$css}}">
  <tr>
    <th class="title_h6" colspan="6">
      <span class="title_h6_spacing">
        {{tr}}CDepistageGrossesse-Immuno-hematology{{/tr}}
      </span>
      <span style="float: right">
          <a href="#antecedents_traitements"
             onclick="DossierMater.selectedMenu($('menu_antecedents_traitements').down('a'));">
            <i class="fas fa-chevron-up actif_arrow"></i>
          </a>
          <a href="#screenings"
             onclick="DossierMater.selectedMenu($('menu_screenings').down('a'));">
            <i class="fas fa-chevron-down actif_arrow"></i>
          </a>
        </span>
    </th>
  </tr>
  <tr>
    {{me_form_field animated=false nb_cells=2 mb_object=$last_depistage mb_field=groupe_sanguin class="card_input"}}
    {{mb_field object=$last_depistage field=groupe_sanguin}}
    {{/me_form_field}}

    <td class="card_input_rhesus">
      <div class="me-padding-bottom-5">{{tr}}CDepistageGrossesse-rhesus{{/tr}}</div>
      <label class="label_rhesus_pos">
        <input type="radio" id="rhesus_pos"
               name="rhesus" value="pos" {{if $last_depistage->rhesus == "pos"}} checked {{/if}}
               onchange="DossierMater.changeColorRhesus(this); DossierMater.ShowElements(this, null, '.rhesus_neg');" />
        <span>{{tr}}CDepistageGrossesse.rhesus.pos-court{{/tr}}</span>
      </label>
      <label class="label_rhesus_neg">
        <input type="radio" id="rhesus_neg"
               name="rhesus" value="neg" {{if $last_depistage->rhesus == "neg"}} checked {{/if}}
               onchange="DossierMater.changeColorRhesus(this); DossierMater.ShowElements(this, null, '.rhesus_neg');" />
        <span>{{tr}}CDepistageGrossesse.rhesus.neg-court{{/tr}}</span>
      </label>
    </td>
  </tr>
  <tr class="rhesus_neg" style="display: none;">
    {{me_form_field animated=false nb_cells=2 mb_object=$last_depistage mb_field=rhesus_bb class="card_input"}}
    {{mb_field object=$last_depistage field=rhesus_bb onchange="DossierMater.ShowElements(this);"}}
    {{/me_form_field}}

    {{me_form_field animated=false nb_cells=2 mb_object=$last_depistage mb_field=date class="me-large-datetime card_input"}}
    {{mb_field object=$last_depistage field=date register=true form="edit_perinatal_folder"}}
    {{/me_form_field}}
  </tr>
  <tr class="rhesus_bb_neg" style="display: none;" >
    {{me_form_field animated=false nb_cells=2 mb_object=$last_depistage mb_field=rques_immuno class="card_input"}}
    {{mb_field object=$last_depistage field=rques_immuno}}
    {{/me_form_field}}
  </tr>
<!--  <tr class="rhesus_neg" style="display: none;">
    {{me_form_bool animated=false nb_cells=2 mb_object=$naissance mb_field=mesures_prophylactiques class="card_input"}}
    {{mb_field object=$naissance field=mesures_prophylactiques}}
    {{/me_form_bool}}

    {{me_form_field animated=false nb_cells=2 mb_object=$naissance mb_field=date_time class="me-large-datetime card_input"}}
    {{mb_field object=$naissance field=date_time register=true form="edit_perinatal_folder"}}
    {{/me_form_field}}
  </tr>
  <tr class="rhesus_proph_neg" style="display: none;">
    {{me_form_field animated=false nb_cells=2 mb_object=$naissance mb_field=autre_mesure_proph_desc class="card_input"}}
    {{mb_field object=$naissance field=autre_mesure_proph_desc}}
    {{/me_form_field}}
  </tr>-->
  <tr>
    {{me_form_field animated=false nb_cells=2 mb_object=$last_depistage mb_field=rai class="card_input"}}
    {{mb_field object=$last_depistage field=rai}}
    {{/me_form_field}}
  </tr>
</table>
