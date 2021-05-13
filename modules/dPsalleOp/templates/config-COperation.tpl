{{*
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="editConfig-COperation" method="post" onsubmit="return onSubmitFormAjax(this);">
  {{mb_configure module=$m}}

  <table class="form">
    {{mb_include module=system template=inc_config_bool var=mode_anesth}}  

    {{assign var="class" value="COperation"}}
    
    <tr>
      <th class="title" colspan="2">{{tr}}config-{{$m}}-{{$class}}{{/tr}}</th>
    </tr>
  
    {{mb_include module=system template=inc_config_bool var=mode}}
    {{mb_include module=system template=inc_config_bool var=modif_salle}}
    {{mb_include module=system template=inc_config_bool var=use_check_timing}}
    <tr>
      <th class="title" colspan="6">Listes déroulantes des timings</th>
    </tr>

    {{assign var="class" value=""}}

    {{mb_include module=system template=inc_config_str var=max_sub_minutes}}
    {{mb_include module=system template=inc_config_str var=max_add_minutes}}

    {{assign var="class" value="COperation"}}

    <tr>
      <th class="title" colspan="2">Affichage des timings</th>
    </tr>
    {{mb_include module=system template=inc_config_bool var=use_entree_bloc}}
    {{mb_include module=system template=inc_config_bool var=use_entree_sortie_salle}}
    {{mb_include module=system template=inc_config_bool var=use_sortie_sans_sspi}}
    {{mb_include module=system template=inc_config_bool var=use_remise_chir}}
    {{mb_include module=system template=inc_config_bool var=use_suture}}
    {{mb_include module=system template=inc_config_bool var=use_garrot}}
    {{mb_include module=system template=inc_config_bool var=use_debut_fin_op}}
    {{mb_include module=system template=inc_config_bool var=use_cleaning_timings}}
    {{mb_include module=system template=inc_config_bool var=use_prep_cutanee}}

    <tr>
      <td class="button" colspan="2">
        <button class="modify">{{tr}}Save{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>