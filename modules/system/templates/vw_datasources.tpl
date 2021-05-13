{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="tbl">
  {{foreach from=$datasources key=_module item=_ds}}
    <tr>
      <th class="category" colspan="3">
        {{if $_module === "_other_"}}
          Autres
        {{else}}
          {{tr}}module-{{$_module}}-court{{/tr}}
        {{/if}}
      </th>
    </tr>
    {{foreach from=$_ds item=_dsn}}
      {{mb_include module=system template=configure_dsn dsn=$_dsn inline=true}}
    {{/foreach}}
  {{/foreach}}
</table>