{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="colorRPU" method="post">
  {{mb_class object=$rpu}}
  {{mb_key   object=$rpu}}
  {{mb_field object=$rpu field=color hidden=1}}
</form>

<form name="categorieRPU" method="post">
  {{mb_class object=$link_cat}}
  {{mb_key   object=$link_cat}}
  <input type="hidden" name="del" value="0" />
  {{mb_field object=$link_cat field=rpu_categorie_id hidden=1}}
  {{mb_field object=$link_cat field=rpu_id hidden=1}}
</form>

<table class="form">
  <tr>
    <th class="title" colspan="5">{{tr}}CRPU-color{{/tr}}</th>
  </tr>
  <tr>
    {{foreach from=1|range:5 item=i}}
    <td>
      {{assign var=color value="dPurgences Placement color_$i"|gconf}}
      {{if $color}}
        <div class="couleur_rpu"
             style="background-color: #{{$color}}; {{if $rpu->color && $rpu->color == $color}}outline: 2px solid #000;{{/if}}"
             data-selected="{{if $rpu->color && $rpu->color == $color}}1{{else}}0{{/if}}"
             onclick="Urgences.updateColor(this, '{{$color}}', '{{$rpu->sejour_id}}')"></div>
      {{/if}}
    </td>
    {{/foreach}}
  </tr>

  <tr>
    <th class="title" colspan="5">{{tr}}CRPU-back-categories_rpu{{/tr}}</th>
  </tr>
  <tr>
    {{assign var=count value=0}}
    {{foreach from=$categories_rpu item=_categorie_rpu name=list_cats}}
      {{math equation=x+1 x=$count assign=count}}

      {{if $count == 6}}
        </tr>
        <tr>

        {{assign var=count value=0}}
      {{/if}}

      {{assign var=selected value=""}}
      {{foreach from=$rpu->_ref_rpu_categories item=_cat}}
        {{if $_cat->rpu_categorie_id == $_categorie_rpu->_id}}
          {{assign var=selected value=$_cat->_id}}
        {{/if}}
      {{/foreach}}

      <td>
        <div id="categorie_rpu_{{$_categorie_rpu->_id}}"
             class="categorie_rpu text"
             style="{{if $selected}}outline: 2px solid #000;{{/if}}"
             data-link_cat_id="{{$selected}}"
             onclick="Urgences.updateCategorie(this, '{{$_categorie_rpu->_id}}', '{{$rpu->sejour_id}}');">
          {{thumbnail document=$_categorie_rpu->_ref_icone profile=small style="width: 20px; height: 20px; background-color: transparent;"}}
          <div class="compact">{{$_categorie_rpu->motif}}</div>
        </div>
      </td>

      {{if $smarty.foreach.list_cats.last}}
        {{math equation=5-x x=$count assign=colspan}}

        {{if $colspan}}
          <td colspan="{{$colspan}}"></td>
        {{/if}}
      {{/if}}
    {{/foreach}}
  </tr>

  <tr>
    <th class="title" colspan="5">{{tr}}CRPU-back-attentes_rpu{{/tr}}</th>
  </tr>
  <tr>
    <td colspan="5">
      {{mb_include module=urgences template=inc_vw_rpu_attente}}
    </td>
  </tr>
  {{if "transport"|module_active}}
    {{assign var=sejour value=$rpu->_ref_sejour}}
    <tr>
      <th class="title" colspan="5">{{tr}}CTransport{{/tr}}</th>
    </tr>
    <tr>
      <td colspan="5" class="button">
        {{mb_include module=transport template=inc_buttons_transport object=$sejour}}
      </td>
    </tr>
  {{/if}}
</table>
