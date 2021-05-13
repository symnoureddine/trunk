{{*
 * @package Mediboard\Bloc
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=show_duree_preop value=$conf.dPplanningOp.COperation.show_duree_preop}}
{{assign var=curr_plage_id value=""}}
{{assign var=salle_id value=""}}
{{assign var=curr_plageop value=""}}
{{assign var="col1" value="dPbloc printing_standard col1"|gconf}}
{{assign var="col2" value="dPbloc printing_standard col2"|gconf}}
{{assign var="col3" value="dPbloc printing_standard col3"|gconf}}

<table class="tbl">
  {{mb_include module=bloc template=inc_view_planning_header}}

  {{foreach from=$listDatesByPrat item=ops_by_date key=curr_date name=date_loop}}
    {{foreach from=$ops_by_date item=listOperations key=prat_id name=user_loop}}
      <tr class="clear">
        <td colspan="{{$_materiel+$_extra+$_duree+$_coordonnees+12}}">
          <h2>
            <strong>{{$curr_date|date_format:"%A %d/%m/%Y"|ucfirst}}</strong>
            &mdash;
            Dr {{mb_include module=mediusers template=inc_vw_mediuser mediuser=$listPrats.$prat_id}}
          </h2>
        </td>
      </tr>

      {{mb_include module=bloc template=inc_view_planning_title}}

      {{mb_include module=bloc template=inc_view_planning_content}}
      {{if $_page_break && !$smarty.foreach.user_loop.last}}
        {{* Firefox ne prend pas en compte les page-break sur les div *}}
        <tr class="clear" style="page-break-after: always;">
          <td colspan="{{$_materiel+$_extra+$_duree+$_coordonnees+12}}" style="border: none;">
            {{* Chrome ne prend pas en compte les page-break sur les tr *}}
            <div style="page-break-after: always;"></div>
          </td>
        </tr>
      {{/if}}
    {{/foreach}}

    {{if $_page_break && !$smarty.foreach.date_loop.last}}
      {{* Firefox ne prend pas en compte les page-break sur les div *}}
      <tr class="clear" style="page-break-after: always;">
        <td colspan="{{$_materiel+$_extra+$_duree+$_coordonnees+12}}" style="border: none;">
          {{* Chrome ne prend pas en compte les page-break sur les tr *}}
          <div style="page-break-after: always;"></div>
        </td>
      </tr>
    {{/if}}
  {{/foreach}}
</table>