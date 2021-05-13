{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
Main.add(function () {
  Calendar.regField(getForm("datasource-typevue").date);
});
</script>

<table class="main">
  <tr>
    <th>
      <form action="" name="datasource-typevue" method="get" onsubmit="return onSubmitFormAjax(this, null, 'datasource_logs_graphs');">
        <input type="hidden" name="m" value="{{$m}}" />
        <input type="hidden" name="a" value="vw_graph_datasource_logs" />
        <input type="hidden" name="to_update" value="1" />

        Journaux de sources de données du
        <input type="hidden" name="date" class="date" value="{{$date}}" onchange="this.form.onsubmit()" />

        <br />
        <label for="interval" title="Echelle d'affichage">Intervalle</label>
        <select name="interval" onchange="this.form.onsubmit();  $('datasource-hours-selectors').setVisible(this.value == 'one-day');">
          <option value="one-day"      {{if $interval == "one-day"     }} selected {{/if}}>1 jour     (par 10mn)    </option>
          <option value="one-week"     {{if $interval == "one-week"    }} selected {{/if}}>1 semaine  (par heure)   </option>
          <option value="eight-weeks"  {{if $interval == "eight-weeks"}}  selected {{/if}}>8 semaines (par jour)    </option>
          <option value="one-year"     {{if $interval == "one-year"    }} selected {{/if}}>1 an       (par semaine) </option>
          <option value="four-years"   {{if $interval == "four-years"  }} selected {{/if}}>4 ans      (par mois)    </option>
          <option value="twenty-years" {{if $interval == "twenty-years"}} selected {{/if}}>20 ans     (par an)      </option>
        </select>

        <span id="datasource-hours-selectors">
          <label for="hour_min" title="Heure minimale">{{tr}}From{{/tr}}</label>
          <select name="hour_min" onchange="this.form.onsubmit()">
            {{foreach from=$hours item=_hour}}
              <option value="{{$_hour}}" {{if $hour_min == $_hour}} selected="selected" {{/if}}>
                {{$_hour|pad:2:0}}h
              </option>
            {{/foreach}}
          </select>

          <label for="hour_max" title="Heure maximale">{{tr}}To{{/tr}}</label>
          <select name="hour_max"  onchange="this.form.onsubmit()">
            {{foreach from=$hours item=_hour}}
              <option value="{{$_hour}}" {{if $hour_max == $_hour}} selected="selected" {{/if}}>
                {{$_hour|pad:2:0}}h
              </option>
            {{/foreach}}
          </select>
        </span>

        <label for="bigsize" title="Afficher en plus grande taille">Grande taille</label>
        <input type="checkbox" name="bigsize" onclick="drawDSGraphs(graphSizes[this.checked ? 1 : 0])" {{if $groupmod == 2}}checked="checked"{{/if}} />
        <br />

        <label for="groupmod" title="Type de vue des graphiques">Type de vue</label>
        <select name="groupmod" onchange="this.form.onsubmit(); this.form.bigsize.checked = this.value == 2;">
          <option value="2" {{if $groupmod == 2}}selected="selected"{{/if}}>Regrouper tout</option>
          <option value="1" {{if $groupmod == 1}}selected="selected"{{/if}}>Regrouper par module</option>
          <optgroup label="Détail du module">
            {{foreach from=$listModules item=curr_module}}
              <option value="{{$curr_module->mod_name}}" {{if $curr_module->mod_name == $module}} selected="selected" {{/if}}>
                {{tr}}module-{{$curr_module->mod_name}}-court{{/tr}}
              </option>
            {{/foreach}}
          </optgroup>
        </select>

        <label for="human_bot" title="Filtrage en fonction du type d'utilisateur">Visualiser</label>
        <select name="human_bot" onchange="this.form.onsubmit()">
          <option value="0" {{if $human_bot === '0'}}selected="selected"{{/if}}>Humains</option>
          <option value="1" {{if $human_bot === '1'}}selected="selected"{{/if}}>Robots</option>
          <option value="2" {{if $human_bot === '2'}}selected="selected"{{/if}}>Les deux</option>
        </select>

        <br />
        <button type="submit" class="search">{{tr}}Search{{/tr}}</button>
      </form>
    </th>
  </tr>

  <tr>
    <td colspan="2">
      <div id="datasource_logs_graphs"></div>
    </td>
  </tr>
</table>