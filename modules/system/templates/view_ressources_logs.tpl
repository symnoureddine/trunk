{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    downloadData = function(oForm) {
      var numelem = oForm.elements.numelem.value;
      $V(oForm.elements.numelem, 0);
      var url = new Url('{{$m}}', 'ajax_resource_logs', 'raw');
      url.addParam('download', 1);
      url.addFormData(oForm);
      url.pop(20, 20, "csv export", null, null, {}, Element.getTempIframe())
      $V(oForm.elements.numelem, numelem);
    };
    
    Calendar.regField(getForm("typevue").date, null, {noView: true});
    getForm('typevue').onsubmit();
  });
</script>

<table class="main">
  <tr>
    <th>
      <form action="?" name="typevue" method="get" target="_blank" onsubmit="return onSubmitFormAjax(this, null, 'search_resource_log');">
        <input type="hidden" name="m" value="{{$m}}" />
        <input type="hidden" name="a" value="ajax_resource_logs" />
        
        Logs d'accès du  {{$date|date_format:$conf.longdate}}
        <input type="hidden" name="date" class="date" value="{{$date}}" onchange="this.form.onsubmit()" />
        
        <label for="interval" title="Echelle d'affichage">Intervalle</label>
        <select name="interval" onchange="this.form.onsubmit()">
          <option value="day"   {{if $interval == "day"  }} selected="selected" {{/if}}>Journée</option>
          <option value="month" {{if $interval == "month"}} selected="selected" {{/if}}>Mois   </option>
          <option value="year"  {{if $interval == "year" }} selected="selected" {{/if}}>Année  </option>
        </select>
        &mdash;
        <label for="numelem" title="Nombre maximum d'éléments à afficher">Eléments maximums</label>
        <input type="text" name="numelem" value="{{$numelem}}" size="2" />
        <br />
        <label for="element" title="Choix de la mesure">Type de mesure</label>
        <select name="element" onchange="this.form.onsubmit()">
          <option value="duration"{{if $element == "duration"}}selected="selected"{{/if}}>Durée totale (php + DB)</option>
          <option value="_average_duration"{{if $element == "_average_duration"}}selected="selected"{{/if}}>Durée totale (php + DB) par hit</option>
          <option value="request"{{if $element == "request"}}selected="selected"{{/if}}>Durée DB</option>
          <option value="_average_request"{{if $element == "_average_request"}}selected="selected"{{/if}}>Durée DB par hit</option>
          <option value="request"{{if $element == "_php_duration"}}selected="selected"{{/if}}>Durée PHP</option>
          <option value="_average_request"{{if $element == "_average_php_duration"}}selected="selected"{{/if}}>Durée PHP par hit</option>
          <option value="nb_requests"{{if $element == "nb_requests"}}selected="selected"{{/if}}>Nombre de requetes</option>
          <option value="_average_nb_requests"{{if $element == "_average_nb_requests"}}selected="selected"{{/if}}>Nombre de requetes par hit</option>
          <option value="hits"{{if $element == "hits"}}selected="selected"{{/if}}>Hits</option>
        </select>
        &mdash;
        <label for="groupres" title="Type de vue des graphiques">Type de vue</label>
        <select name="groupres" onchange="this.form.onsubmit()">
          <option value="0"{{if $groupres == 0}}selected="selected"{{/if}}>Regrouper par module</option>
          <option value="1"{{if $groupres == 1}}selected="selected"{{/if}}>Regrouper tout</option>
        </select>
        <br />
        <button type="submit" class="search">{{tr}}Search{{/tr}}</button>
        <button type="button" class="download" onclick="downloadData(this.form);">{{tr}}Download{{/tr}}</button>
      </form>
    </th>
  </tr>

  <tbody id="search_resource_log"></tbody>
</table>