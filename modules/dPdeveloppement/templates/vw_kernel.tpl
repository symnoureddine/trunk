{{*
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    $("list-listeners-container").fixedTableHeaders();
  });
</script>

<div id="list-listeners-container">
  <table class="main tbl">
    <thead>
    <tr>
      <th class="narrow">Evènement</th>
      <th class="narrow">Priorité</th>
      <th class="narrow">Type</th>
      <th>Callable</th>
    </tr>
    </thead>

    <tbody>
    {{foreach from=$listeners key=_event_name item=_listeners}}
      {{assign var=first_group value=true}}
      {{foreach from=$_listeners item=_infos}}
        <tr>
          {{if $first_group == true}}
            {{assign var=first_group value=false}}
            <td rowspan="{{$_listeners|@count}}">
              <strong>{{$_event_name}}</strong>
            </td>
          {{/if}}

          <td class="me-text-align-right">
            <strong>{{$_infos.priority}}</strong>
          </td>

          {{assign var=color value=me-color-info}}
          {{if $_infos.type == 'api'}}
            {{assign var=color value=me-color-error}}
          {{else}}
            {{if $_infos.type == 'optionnal'}}
              {{assign var=color value=me-color-success}}
            {{/if}}
          {{/if}}

          <td class="{{$color}}">
            <strong>{{$_infos.type}}</strong>
          </td>

          <td>{{$_infos.callable}}</td>
        </tr>
      {{/foreach}}
    {{/foreach}}
    </tbody>
  </table>
</div>
